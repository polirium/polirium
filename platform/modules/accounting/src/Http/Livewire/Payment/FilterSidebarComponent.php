<?php

namespace Polirium\Modules\Accounting\Http\Livewire\Payment;

use Illuminate\Support\Carbon;
use Livewire\Component;

class FilterSidebarComponent extends Component
{
    public $users = [];
    public $saleChannels = [];
    public $deliveryPartners = [];
    public $paymentMethods = [];

    // Customer search
    public $customerSearch = '';
    public $customerResults = [];

    public $search = [
        'code' => '',
        'status' => '',
        'user_id' => '',
        'date' => '',
        'sale_channel_id' => '',
        'customer_id' => '',
        'order_type' => '',
        'delivery_partner_id' => '',
        'type_payment_method' => '',
    ];

    public array $statusChecked = [];

    public $statuses = [];
    public $orderTypeOptions = [];

    public function mount(): void
    {
        if (($this->search['date'] ?? '') === '' && ! request()->query('search')) {
            $this->search['date'] = Carbon::today()->toDateString();
        }

        $this->statuses = [
            'pending' => __('modules/accounting::accounting.pending'),
            'success' => __('modules/accounting::accounting.completed'),
            'draft' => __('modules/accounting::accounting.draft'),
            'cancel' => __('modules/accounting::accounting.cancelled'),
            'delivery_failed' => __('modules/accounting::accounting.delivery_failed'),
        ];

        // Default: all checked except cancel & delivery_failed
        $this->statusChecked = ['pending', 'success', 'draft'];
        $this->dispatch('datatable-payment-filter', $this->statusChecked, 'status_checked');

        $this->orderTypeOptions = [
            '' => __('core/base::general.all'),
            'collected' => trans('modules/accounting::accounting.collected'),
            'pending' => trans('modules/accounting::accounting.remaining_receivable'),
        ];

        // Users (nhân viên bán hàng) - limit 100
        $this->users = \Polirium\Core\Base\Http\Models\User::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->limit(100)
            ->get();

        // Sale Channels - only active, limit 50
        $this->saleChannels = \Polirium\Modules\Product\Http\Model\Payment\SaleChannel::query()
            ->where('is_active', true)
            ->select(['id', 'name'])
            ->orderBy('name')
            ->limit(50)
            ->pluck('name', 'id')
            ->toArray();

        // Delivery Partners - only active, limit 50
        $this->deliveryPartners = \Polirium\Modules\Product\Http\Model\Payment\PaymentPartnerDelivery::query()
            ->where('is_active', true)
            ->select(['id', 'name', 'code'])
            ->orderBy('name')
            ->limit(50)
            ->get();

        // Payment Methods - only active, limit 50
        $this->paymentMethods = \Polirium\Modules\Product\Http\Model\Payment\PaymentMethod::query()
            ->where('is_active', true)
            ->select(['id', 'name', 'code'])
            ->orderBy('name')
            ->limit(50)
            ->get()
            ->toArray();

        // Don't load all customers - will search on demand
        $this->customerResults = [];

        // Load selected customer if any
        if (! empty($this->search['customer_id'])) {
            $this->loadSelectedCustomer();
        }
    }

    public function updatedSearch($value, $key)
    {
        $this->dispatch('datatable-payment-filter', $value, $key);
    }

    public function toggleStatus(string $status): void
    {
        if (in_array($status, $this->statusChecked)) {
            $this->statusChecked = array_values(array_diff($this->statusChecked, [$status]));
        } else {
            $this->statusChecked[] = $status;
        }
        $this->dispatch('datatable-payment-filter', $this->statusChecked, 'status_checked');
    }

    public function toggleAllStatuses(): void
    {
        if (count($this->statusChecked) === count($this->statuses)) {
            $this->statusChecked = [];
        } else {
            $this->statusChecked = array_keys($this->statuses);
        }
        $this->dispatch('datatable-payment-filter', $this->statusChecked, 'status_checked');
    }

    public function updatedCustomerSearch($value)
    {
        if (strlen($value) < 2) {
            $this->customerResults = [];

            return;
        }

        $this->customerResults = \Polirium\Modules\Customer\Http\Model\Customer::query()
            ->select(['id', 'name', 'code'])
            ->where(function ($q) use ($value) {
                $q->where('name', 'like', '%' . $value . '%')
                  ->orWhere('code', 'like', '%' . $value . '%')
                  ->orWhere('phone', 'like', '%' . $value . '%');
            })
            ->orderBy('name')
            ->limit(20)
            ->get();
    }

    public function updatedSearchCustomerId($value)
    {
        $this->customerSearch = '';
        $this->loadSelectedCustomer();
    }

    private function loadSelectedCustomer()
    {
        if (empty($this->search['customer_id'])) {
            $this->customerResults = [];

            return;
        }

        $customer = \Polirium\Modules\Customer\Http\Model\Customer::query()
            ->select(['id', 'name', 'code'])
            ->find($this->search['customer_id']);

        $this->customerResults = $customer ? collect([$customer]) : collect([]);
    }

    public function selectCustomer($customerId)
    {
        $this->search['customer_id'] = $customerId;
        $this->customerSearch = '';
        $this->loadSelectedCustomer();
        $this->dispatch('datatable-payment-filter', $customerId, 'customer_id');
    }

    public function clearFilter(): void
    {
        $this->search = [
            'code' => '',
            'status' => '',
            'user_id' => '',
            'date' => '',
            'sale_channel_id' => '',
            'customer_id' => '',
            'order_type' => '',
            'delivery_partner_id' => '',
            'type_payment_method' => '',
        ];
        $this->statusChecked = ['pending', 'success', 'draft'];
        $this->customerSearch = '';
        $this->customerResults = [];
        $this->dispatch('datatable-payment-filter-clear');
    }

    public function render()
    {
        return view('modules/accounting::payment.filter-sidebar');
    }
}
