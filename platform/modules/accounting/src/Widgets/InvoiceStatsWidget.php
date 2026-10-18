<?php

namespace Polirium\Modules\Accounting\Widgets;

use Polirium\Core\Base\Widgets\AbstractWidget;
use Polirium\Modules\Accounting\Http\Model\Payment;
use Polirium\Modules\Accounting\Http\Model\Receipt;

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

    public static function getPermissions(): array
    {
        return [
            'accountings.dashboard',
        ];
    }

    protected static function getComponentName(): string
    {
        return 'modules/accounting::widgets.invoice-stats';
    }

    public function setPeriod(string $period): void
    {
        $this->period = in_array($period, ['today', 'week', 'month'], true) ? $period : 'today';
    }

    /**
     * Total and count of cash book vouchers (receipts or payments) in a range.
     *
     * @param class-string<\Illuminate\Database\Eloquent\Model> $model
     */
    protected function totals(string $model, array $range): object
    {
        return $model::query()
            ->whereDate('date', '>=', $range[0]->toDateString())
            ->whereDate('date', '<=', $range[1]->toDateString())
            ->when(user_branch(), fn ($query, $branchId) => $query->where('branch_id', $branchId))
            ->selectRaw('COUNT(*) AS n, COALESCE(SUM(value), 0) AS total')
            ->first();
    }

    public function render()
    {
        $range = $this->periodRange($this->period);
        $previous = $this->previousRange($this->period);

        // Phiếu thu are receipts (accounting_receipts), phiếu chi are payments.
        $receipts = $this->totals(Receipt::class, $range);
        $expenses = $this->totals(Payment::class, $range);
        $previousReceipts = $this->totals(Receipt::class, $previous);
        $previousExpenses = $this->totals(Payment::class, $previous);

        return view('modules/accounting::widgets.invoice-stats', [
            'period' => $this->period,
            'receipts' => (float) $receipts->total,
            'expenses' => (float) $expenses->total,
            'receiptCount' => (int) $receipts->n,
            'expenseCount' => (int) $expenses->n,
            'receiptChange' => $this->change((float) $receipts->total, (float) $previousReceipts->total),
            'expenseChange' => $this->change((float) $expenses->total, (float) $previousExpenses->total),
        ]);
    }
}
