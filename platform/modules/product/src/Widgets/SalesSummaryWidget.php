<?php

namespace Polirium\Modules\Product\Widgets;

use Illuminate\Support\Facades\DB;
use Polirium\Core\Base\Widgets\AbstractWidget;
use Polirium\Modules\Product\Http\Model\Payment\Payment;
use Polirium\Modules\Product\Http\Model\Payment\PaymentProduct;

/**
 * Sales Summary Widget
 *
 * Shows sales summary including top products and order statistics.
 */
class SalesSummaryWidget extends AbstractWidget
{
    public string $period = 'today';

    public static function getWidgetId(): string
    {
        return 'product.sales-summary';
    }

    public static function getWidgetName(): string
    {
        return 'Tóm tắt bán hàng';
    }

    public static function getIcon(): string
    {
        return 'chart-pie';
    }

    public static function getDescription(): string
    {
        return 'Thống kê tóm tắt hoạt động bán hàng';
    }

    public static function getDefaultWidth(): int
    {
        return 6;
    }

    public static function getDefaultHeight(): int
    {
        return 2;
    }

    public static function getPermissions(): array
    {
        return [
            'widgets.sales',
        ];
    }

    protected static function getComponentName(): string
    {
        return 'modules/product::widgets.sales-summary';
    }

    public function setPeriod(string $period): void
    {
        $this->period = in_array($period, ['today', 'week', 'month'], true) ? $period : 'today';
    }

    /**
     * Sales of a range that count (not drafts or cancelled), in this branch.
     */
    protected function sales(array $range)
    {
        return Payment::query()
            ->whereBetween('created_at', $range)
            ->whereNotIn('status', ['draft', 'temp', 'cancelled', 'cancel', 'failed', 'delivery_failed'])
            ->when(user_branch(), fn ($query, $branchId) => $query->where('branch_id', $branchId));
    }

    /**
     * Orders, items and revenue of a range, added up in the database.
     */
    protected function totals(array $range): object
    {
        return $this->sales($range)->toBase()
            ->selectRaw('COUNT(*) AS orders, COALESCE(SUM(amount_products), 0) AS items, COALESCE(SUM(value), 0) AS revenue')
            ->selectRaw('COUNT(DISTINCT customer_id) AS customers, COUNT(customer_id) AS with_customer')
            ->first();
    }

    public function render()
    {
        $range = $this->periodRange($this->period);
        $current = $this->totals($range);
        $previous = $this->totals($this->previousRange($this->period));

        $buckets = array_fill_keys($this->periodBuckets($this->period), 0);
        $series = ['orders' => $buckets, 'items' => $buckets, 'revenue' => $buckets];
        $bucketSql = $this->bucketSql($this->period);
        $rows = $this->sales($range)->toBase()
            ->selectRaw("{$bucketSql} AS bucket, COUNT(*) AS orders, COALESCE(SUM(amount_products), 0) AS items, COALESCE(SUM(value), 0) AS revenue")
            ->groupByRaw($bucketSql)
            ->get();
        foreach ($rows as $row) {
            $bucket = (string) $row->bucket;
            if (array_key_exists($bucket, $buckets)) {
                $series['orders'][$bucket] = (int) $row->orders;
                $series['items'][$bucket] = (int) $row->items;
                $series['revenue'][$bucket] = (float) $row->revenue;
            }
        }

        $totalOrders = (int) $current->orders;
        $totalItems = (int) $current->items;
        $totalRevenue = (float) $current->revenue;
        $avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;
        $previousAvg = (int) $previous->orders > 0 ? (float) $previous->revenue / (int) $previous->orders : 0;

        $topProducts = PaymentProduct::query()
            ->select('product_id', DB::raw('SUM(amount) AS total_sold'), DB::raw('SUM(total) AS total_value'))
            ->whereIn('product_payment_id', $this->sales($range)->select('id'))
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->limit(4)
            ->with('product:id,name,code')
            ->get();

        return view('modules/product::widgets.sales-summary', [
            'period' => $this->period,
            'totalOrders' => $totalOrders,
            'totalItems' => $totalItems,
            'totalRevenue' => $totalRevenue,
            'avgOrderValue' => $avgOrderValue,
            'customers' => (int) $current->customers,
            'withCustomer' => $totalOrders > 0 ? round((int) $current->with_customer / $totalOrders * 100) : 0,
            'changes' => [
                'orders' => $this->change($totalOrders, (float) $previous->orders),
                'items' => $this->change($totalItems, (float) $previous->items),
                'revenue' => $this->change($totalRevenue, (float) $previous->revenue),
                'avg' => $this->change($avgOrderValue, $previousAvg),
            ],
            'series' => array_map('array_values', $series),
            'topProducts' => $topProducts,
        ]);
    }
}
