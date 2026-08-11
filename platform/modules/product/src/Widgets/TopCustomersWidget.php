<?php

namespace Polirium\Modules\Product\Widgets;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Polirium\Core\Base\Widgets\AbstractWidget;

class TopCustomersWidget extends AbstractWidget
{
    public string $period = 'today';

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

        $query = DB::table('product_payments')
            ->join('customers', 'customers.id', '=', 'product_payments.customer_id')
            ->where('product_payments.status', 'success')
            ->whereBetween($saleTime, [$startDate, $endDate])
            ->when(user_branch(), function ($query, $branchId): void {
                $query->where('product_payments.branch_id', $branchId);
            });

        $totalCustomers = (clone $query)->distinct()->count('customers.id');

        $customers = $query
            ->select(['customers.id', 'customers.name', 'customers.phone'])
            ->selectRaw('COUNT(product_payments.id) AS total_orders')
            ->selectRaw('SUM(COALESCE(product_payments.amount_products, 0)) AS total_quantity')
            ->groupBy('customers.id', 'customers.name', 'customers.phone')
            ->orderByDesc('total_quantity')
            ->orderByDesc('total_orders')
            ->limit(5)
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
        ]);
    }
}
