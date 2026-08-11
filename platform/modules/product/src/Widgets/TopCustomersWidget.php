<?php

namespace Polirium\Modules\Product\Widgets;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Polirium\Core\Base\Widgets\AbstractWidget;

class TopCustomersWidget extends AbstractWidget
{
    public string $period = 'today';

    public int $currentPage = 1;

    private const PER_PAGE = 5;

    public static function getWidgetId(): string
    {
        return 'product.top-customers';
    }

    public static function getWidgetName(): string
    {
        return 'Khách hàng mua nhiều';
    }

    public static function getIcon(): string
    {
        return 'users-group';
    }

    public static function getDescription(): string
    {
        return 'Xếp hạng khách hàng theo số lượng sản phẩm đã mua';
    }

    public static function getDefaultWidth(): int
    {
        return 6;
    }

    public static function getDefaultHeight(): int
    {
        return 3;
    }

    public static function getPermissions(): array
    {
        return ['widgets.sales'];
    }

    protected static function getComponentName(): string
    {
        return 'modules/product::widgets.top-customers';
    }

    public function setPeriod(string $period): void
    {
        $this->period = in_array($period, ['today', 'week', 'month', 'year'], true)
            ? $period
            : 'today';
        $this->currentPage = 1;
    }

    public function previousPage(): void
    {
        $this->currentPage = max(1, $this->currentPage - 1);
    }

    public function nextPage(): void
    {
        $this->currentPage++;
    }

    protected function getDateRange(): array
    {
        return match ($this->period) {
            'week' => [Carbon::now()->startOfWeek(), Carbon::now()],
            'month' => [Carbon::now()->startOfMonth(), Carbon::now()],
            'year' => [Carbon::now()->startOfYear(), Carbon::now()],
            default => [Carbon::today(), Carbon::now()],
        };
    }

    public static function maskPhone(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';

        if (strlen($digits) === 11 && str_starts_with($digits, '84')) {
            $digits = '0'.substr($digits, 2);
        }

        if (strlen($digits) < 7) {
            return $digits === '' ? '—' : str_repeat('x', strlen($digits));
        }

        return substr($digits, 0, 3).'xxxx'.substr($digits, -3);
    }

    public function render()
    {
        [$startDate, $endDate] = $this->getDateRange();
        $saleTime = DB::raw('COALESCE(product_payments.completed_at, product_payments.created_at)');
        $paymentLines = DB::table('product_payment_products')
            ->selectRaw('product_payment_id, SUM(amount) AS total_quantity, SUM(total) AS line_total')
            ->groupBy('product_payment_id');

        $query = DB::table('product_payments')
            ->join('customers', 'customers.id', '=', 'product_payments.customer_id')
            ->leftJoinSub($paymentLines, 'payment_lines', function ($join): void {
                $join->on('payment_lines.product_payment_id', '=', 'product_payments.id');
            })
            ->where('product_payments.status', 'success')
            ->whereBetween($saleTime, [$startDate, $endDate])
            ->when(user_branch(), function ($query, $branchId): void {
                $query->where('product_payments.branch_id', $branchId);
            });

        $totalCustomers = (clone $query)->distinct()->count('customers.id');
        $totalPages = max(1, (int) ceil($totalCustomers / self::PER_PAGE));
        $this->currentPage = min(max(1, $this->currentPage), $totalPages);

        $customers = $query
            ->select(['customers.id', 'customers.name', 'customers.phone'])
            ->selectRaw('COUNT(product_payments.id) AS total_orders')
            ->selectRaw('SUM(COALESCE(payment_lines.total_quantity, 0)) AS total_quantity')
            ->selectRaw('SUM(CASE WHEN product_payments.value > 0 THEN product_payments.value WHEN product_payments.total_cost > 0 THEN product_payments.total_cost ELSE COALESCE(payment_lines.line_total, 0) END) AS total_spent')
            ->groupBy('customers.id', 'customers.name', 'customers.phone')
            ->orderByDesc('total_quantity')
            ->orderByDesc('total_spent')
            ->orderByDesc('total_orders')
            ->offset(($this->currentPage - 1) * self::PER_PAGE)
            ->limit(self::PER_PAGE)
            ->get()
            ->map(function ($customer) {
                $customer->masked_phone = self::maskPhone($customer->phone);

                return $customer;
            });

        return view('modules/product::widgets.top-customers', [
            'period' => $this->period,
            'customers' => $customers,
            'totalCustomers' => $totalCustomers,
            'maxQuantity' => max(1, (int) $customers->max('total_quantity')),
            'currentPage' => $this->currentPage,
            'totalPages' => $totalPages,
            'perPage' => self::PER_PAGE,
        ]);
    }
}
