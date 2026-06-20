<?php

namespace Polirium\Modules\Accounting\Http\Livewire\Dashboard;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;
use Polirium\Modules\Product\Http\Model\Payment\Payment;

class AccountingDashboardComponent extends Component
{
    public $revenue = 0;
    public $discount = 0;
    public $payable = 0;
    public $paid = 0;
    public $debt = 0;
    public $cogs = 0;

    public $methods = [];
    public $channels = [];
    public $partners = [];

    public ?string $dateFrom = null;
    public ?string $dateTo = null;

    public function mount()
    {
        $this->dateFrom = now()->toDateString();
        $this->dateTo = now()->toDateString();
        $this->calculateStats();
    }

    public function calculateStats()
    {
        $query = $this->invoiceQuery();

        // Revenue (Doanh thu - usually Price - Discount, i.e., Value)
        if (auth()->user()->can('accountings.dashboard.revenue')) {
            $this->revenue = (clone $query)->sum('value');
        }

        // Discount
        if (auth()->user()->can('accountings.dashboard.discount')) {
            $this->discount = (clone $query)->sum('discount_value');
        }

        // Payable (Khách cần trả - distinct from Revenue? Usually same as Revenue if no taxes/fees extra)
        if (auth()->user()->can('accountings.dashboard.payable')) {
            $this->payable = (clone $query)->sum('value');
        }

        // Paid
        if (auth()->user()->can('accountings.dashboard.paid')) {
            $this->paid = (clone $query)->sum('value_payment');
        }

        // Debt
        if (auth()->user()->can('accountings.dashboard.debt')) {
            // value - value_payment
            $this->debt = (clone $query)->sum(DB::raw('value - value_payment'));
        }

        // COGS
        if (auth()->user()->can('accountings.dashboard.cogs')) {
            $this->cogs = (clone $query)
               ->join('product_payment_products', 'product_payments.id', '=', 'product_payment_products.product_payment_id')
               ->join('products', 'product_payment_products.product_id', '=', 'products.id')
               ->sum(DB::raw('product_payment_products.amount * products.cost'));
        }

        // Methods
        if (auth()->user()->can('accountings.dashboard.payment_methods')) {
            $payments = (clone $query)->select('type_payment')->get();
            $methodStats = [];
            foreach ($payments as $payment) {
                $types = is_string($payment->type_payment) ? json_decode($payment->type_payment, true) : $payment->type_payment;
                if (is_array($types)) {
                    foreach ($types as $type) {
                        $method = $type['method'] ?? 'unknown';
                        $val = $type['value'] ?? 0;
                        if (! isset($methodStats[$method])) {
                            $methodStats[$method] = 0;
                        }
                        $methodStats[$method] += $val;
                    }
                }
            }
            $this->methods = $methodStats;
        }

        // Channels
        if (auth()->user()->can('accountings.dashboard.sale_channels')) {
            $this->channels = (clone $query)->select('sale_channel_id', DB::raw('sum(value) as total'))
                ->groupBy('sale_channel_id')
                ->with('saleChannel')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->saleChannel->name ?? 'N/A' => $item->total];
                })->toArray();
        }

        // Delivery Partners
        if (auth()->user()->can('accountings.dashboard.delivery_partners')) {
            $this->partners = (clone $query)
               ->join('product_payment_deliveries', 'product_payments.id', '=', 'product_payment_deliveries.product_payment_id')
               ->join('product_payment_partner_deliveries', 'product_payment_deliveries.partner_delivery_id', '=', 'product_payment_partner_deliveries.id')
               ->select('product_payment_partner_deliveries.name', DB::raw('sum(product_payments.value) as total'))
                ->groupBy('product_payment_partner_deliveries.name')
                ->pluck('total', 'name')
                ->toArray();
        }
    }

    #[On('accounting-dashboard-date-range')]
    public function updateDateRange(?string $dateFrom = null, ?string $dateTo = null): void
    {
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->calculateStats();
    }

    private function invoiceQuery(): Builder
    {
        $query = Payment::query()
            ->where('product_payments.status', 'success')
            ->when(user_branch(), function (Builder $query) {
                $query->where('product_payments.branch_id', user_branch());
            });

        if ($this->dateFrom) {
            $query->where('product_payments.created_at', '>=', Carbon::parse($this->dateFrom)->startOfDay());
        }

        if ($this->dateTo) {
            $query->where('product_payments.created_at', '<=', Carbon::parse($this->dateTo)->endOfDay());
        }

        return $query;
    }

    public function render()
    {
        return view('modules/accounting::dashboard.widgets');
    }
}
