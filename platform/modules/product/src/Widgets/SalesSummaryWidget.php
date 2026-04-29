<?php

namespace Polirium\Modules\Product\Widgets;

use Carbon\Carbon;
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
        $this->period = $period;
    }

    protected function getDateRange(): array
    {
        return match ($this->period) {
            'today' => [Carbon::today(), Carbon::now()],
            'week' => [Carbon::now()->startOfWeek(), Carbon::now()],
            'month' => [Carbon::now()->startOfMonth(), Carbon::now()],
            default => [Carbon::today(), Carbon::now()],
        };
    }

    public function render()
    {
        [$startDate, $endDate] = $this->getDateRange();

        $payments = Payment::whereBetween('created_at', [$startDate, $endDate])
            ->whereNotIn('status', ['cancelled', 'cancel', 'failed'])
            ->get();

        $totalOrders = $payments->count();
        $totalItems = $payments->sum('amount_products');
        $totalRevenue = $payments->sum('value_payment');
        $avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

        // Top products
        $topProducts = PaymentProduct::select('product_id', DB::raw('SUM(amount) as total_sold'))
            ->whereHas('payment', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate])
                      ->whereNotIn('status', ['cancelled', 'cancel', 'failed']);
            })
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->limit(5)
            ->with('product:id,name,code')
            ->get();

        return view('modules/product::widgets.sales-summary', [
            'period' => $this->period,
            'totalOrders' => $totalOrders,
            'totalItems' => $totalItems,
            'totalRevenue' => $totalRevenue,
            'avgOrderValue' => $avgOrderValue,
            'topProducts' => $topProducts,
        ]);
    }
}
