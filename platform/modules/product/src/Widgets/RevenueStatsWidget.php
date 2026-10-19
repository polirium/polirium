<?php

namespace Polirium\Modules\Product\Widgets;

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
        $this->period = in_array($period, ['today', 'week', 'month'], true) ? $period : 'today';
    }

    /**
     * Sales of a range that count as revenue (not drafts or cancelled), in this branch.
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
        $totals = $this->sales($range)->toBase()
            ->selectRaw('COUNT(*) AS orders, COALESCE(SUM(value), 0) AS revenue')
            ->first();

        $series = array_fill_keys($this->periodBuckets($this->period), 0);
        $bucketSql = $this->bucketSql($this->period);
        $rows = $this->sales($range)->toBase()
            ->selectRaw("{$bucketSql} AS bucket, COALESCE(SUM(value), 0) AS revenue")
            ->groupByRaw($bucketSql)
            ->get();
        foreach ($rows as $row) {
            if (array_key_exists((string) $row->bucket, $series)) {
                $series[(string) $row->bucket] = (float) $row->revenue;
            }
        }

        // Collected money per payment method (from each invoice's own payment
        // lines); what is still owed (e.g. COD on its way) is "not collected".
        // The lines are JSON, so they are read in chunks, not all sales at once.
        $methods = ['cash' => 0, 'bank' => 0, 'card' => 0, 'other' => 0];
        $this->sales($range)->select(['id', 'type_payment'])->lazyById(500)->each(function (Payment $payment) use (&$methods) {
            foreach ((array) $payment->type_payment as $line) {
                $method = is_array($line) ? ($line['method'] ?? 'other') : (string) $line;
                $value = is_array($line) ? (float) ($line['value'] ?? 0) : 0;
                $key = match ($method) {
                    'cash' => 'cash',
                    'bank', 'transfer' => 'bank',
                    'card' => 'card',
                    default => 'other',
                };
                $methods[$key] += $value;
            }
        });

        $totalRevenue = (float) $totals->revenue;
        $collected = array_sum($methods);
        $uncollected = max(0, $totalRevenue - $collected);
        $previousRevenue = (float) $this->sales($this->previousRange($this->period))->sum('value');

        $parts = [
            ['label' => trans('modules/product::product.cash'), 'value' => $methods['cash'], 'icon' => 'cash', 'color' => 'success'],
            ['label' => trans('modules/product::product.bank_transfer'), 'value' => $methods['bank'], 'icon' => 'building-bank', 'color' => 'primary'],
            ['label' => trans('modules/product::product.card'), 'value' => $methods['card'], 'icon' => 'credit-card', 'color' => 'purple'],
            ['label' => trans('core/base::general.collected_other'), 'value' => $methods['other'], 'icon' => 'wallet', 'color' => 'cyan'],
            ['label' => trans('core/base::general.not_collected'), 'value' => $uncollected, 'icon' => 'clock-dollar', 'color' => 'warning'],
        ];

        return view('modules/product::widgets.revenue-stats', [
            'period' => $this->period,
            'totalRevenue' => $totalRevenue,
            'totalOrders' => (int) $totals->orders,
            'change' => $this->change($totalRevenue, $previousRevenue),
            'series' => array_values($series),
            'parts' => array_values(array_filter($parts, fn ($part) => $part['value'] > 0 || in_array($part['icon'], ['cash', 'building-bank', 'credit-card'], true))),
        ]);
    }
}
