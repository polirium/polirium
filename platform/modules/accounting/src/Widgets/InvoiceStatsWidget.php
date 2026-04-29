<?php

namespace Polirium\Modules\Accounting\Widgets;

use Carbon\Carbon;
use Polirium\Core\Base\Widgets\AbstractWidget;
use Polirium\Modules\Accounting\Http\Model\Payment;

/**
 * Invoice Stats Widget
 *
 * Shows invoice and accounting statistics.
 */
class InvoiceStatsWidget extends AbstractWidget
{
    public string $period = 'today';

    public static function getWidgetId(): string
    {
        return 'accounting.invoice-stats';
    }

    public static function getWidgetName(): string
    {
        return 'Thống kê thu chi';
    }

    public static function getIcon(): string
    {
        return 'file-invoice';
    }

    public static function getDescription(): string
    {
        return 'Thống kê hóa đơn thu chi theo kỳ';
    }

    public static function getDefaultWidth(): int
    {
        return 6;
    }

    public static function getDefaultHeight(): int
    {
        return 2;
    }

    protected static function getComponentName(): string
    {
        return 'modules/accounting::widgets.invoice-stats';
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

        // type_id = 1: Thu, type_id = 2: Chi (assumed based on typical accounting)
        $receipts = Payment::whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->where('type_id', 1)
            ->sum('value');

        $expenses = Payment::whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->where('type_id', 2)
            ->sum('value');

        $receiptCount = Payment::whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->where('type_id', 1)
            ->count();

        $expenseCount = Payment::whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->where('type_id', 2)
            ->count();

        $balance = $receipts - $expenses;

        return view('modules/accounting::widgets.invoice-stats', [
            'period' => $this->period,
            'receipts' => $receipts,
            'expenses' => $expenses,
            'receiptCount' => $receiptCount,
            'expenseCount' => $expenseCount,
            'balance' => $balance,
        ]);
    }
}
