<?php

namespace Polirium\Modules\Product\Widgets;

use Carbon\Carbon;
use Polirium\Core\Base\Widgets\AbstractWidget;
use Polirium\Modules\Product\Http\Model\Payment\Payment;

/**
 * Revenue Stats Widget
 *
 * Shows revenue statistics by payment type (cash, bank, card).
 */
class RevenueStatsWidget extends AbstractWidget
{
    public string $period = 'today';

    public static function getWidgetId(): string
    {
        return 'product.revenue-stats';
    }

    public static function getWidgetName(): string
    {
        return 'Thống kê doanh thu';
    }

    public static function getIcon(): string
    {
        return 'cash';
    }

    public static function getDescription(): string
    {
        return 'Thống kê doanh thu theo hình thức thanh toán';
    }

    public static function getDefaultWidth(): int
    {
        return 12;
    }

    public static function getDefaultHeight(): int
    {
        return 2;
    }

    public static function getPermissions(): array
    {
        return [
            'widgets.revenue',
        ];
    }

    protected static function getComponentName(): string
    {
        return 'modules/product::widgets.revenue-stats';
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

        $totalCash = 0;
        $totalBank = 0;
        $totalCard = 0;
        $totalOrders = $payments->count();

        foreach ($payments as $payment) {
            $types = $payment->type_payment ?? [];
            $amount = $payment->value_payment ?? 0;

            if (in_array('cash', $types)) {
                $totalCash += $amount;
            }
            if (in_array('bank', $types)) {
                $totalBank += $amount;
            }
            if (in_array('card', $types)) {
                $totalCard += $amount;
            }
        }

        $totalRevenue = $totalCash + $totalBank + $totalCard;

        return view('modules/product::widgets.revenue-stats', [
            'period' => $this->period,
            'totalRevenue' => $totalRevenue,
            'totalOrders' => $totalOrders,
            'stats' => [
                [
                    'label' => 'Tiền mặt',
                    'value' => $totalCash,
                    'icon' => 'cash',
                    'color' => 'success',
                ],
                [
                    'label' => 'Chuyển khoản',
                    'value' => $totalBank,
                    'icon' => 'building-bank',
                    'color' => 'primary',
                ],
                [
                    'label' => 'Thẻ',
                    'value' => $totalCard,
                    'icon' => 'credit-card',
                    'color' => 'info',
                ],
            ],
        ]);
    }
}
