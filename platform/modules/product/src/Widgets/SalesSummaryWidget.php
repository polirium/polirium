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

    public function render()
    {
        $range = $this->periodRange($this->period);
        $payments = $this->sales($range)->get(['id', 'customer_id', 'value', 'amount_products', 'created_at']);
        $previous = $this->sales($this->previousRange($this->period))
            ->selectRaw('COUNT(*) AS orders, COALESCE(SUM(amount_products), 0) AS items, COALESCE(SUM(value), 0) AS revenue')
            ->first();

        $buckets = array_fill_keys($this->periodBuckets($this->period), 0);
        $series = ['orders' => $buckets, 'items' => $buckets, 'revenue' => $buckets];
        foreach ($payments as $payment) {
            $bucket = $this->bucketOf($this->period, $payment->created_at);
            if (array_key_exists($bucket, $buckets)) {
                $series['orders'][$bucket]++;
                $series['items'][$bucket] += (int) $payment->amount_products;
                $series['revenue'][$bucket] += (float) $payment->value;
            }
        }

        $totalOrders = $payments->count();
        $totalItems = (int) $payments->sum('amount_products');
        $totalRevenue = (float) $payments->sum('value');
        $avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;
        $previousAvg = (int) $previous->orders > 0 ? (float) $previous->revenue / (int) $previous->orders : 0;

        $topProducts = PaymentProduct::query()
            ->select('product_id', DB::raw('SUM(amount) AS total_sold'), DB::raw('SUM(total) AS total_value'))
            ->whereIn('product_payment_id', $payments->pluck('id'))
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
            'customers' => $payments->whereNotNull('customer_id')->pluck('customer_id')->unique()->count(),
            'withCustomer' => $totalOrders > 0 ? round($payments->whereNotNull('customer_id')->count() / $totalOrders * 100) : 0,
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
