<?php

namespace Polirium\Modules\Accounting\Http\Livewire\Payment\Modal;

use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Polirium\Modules\Customer\Http\Model\Customer;
use Polirium\Modules\Product\Http\Model\Payment\Payment;
use Polirium\Modules\Product\Http\Model\Payment\PaymentDelivery;

use Polirium\Modules\Product\Http\Model\Payment\PaymentMethod;
use Polirium\Modules\Product\Http\Model\Payment\PaymentPartnerDelivery;
use Polirium\Modules\Product\Http\Model\Payment\SaleChannel;

class ModalQuickUpdateComponent extends Component
{
    public $paymentId;
    public $sale_channel_id;
    public $partner_delivery_id;
    public $delivery_code;
    public $payment_method_code;
    public $customer_id;
    public $search_customer = '';

    // Lists for selects
    public $saleChannels = [];
    public $paymentMethods = [];
    public $deliveryPartners = [];

    protected $listeners = [
        'show-modal-quick-update' => 'show',
        'sale-channel-created' => 'onSaleChannelCreated',
        'partner-delivery-created' => 'onPartnerDeliveryCreated',
        'customer-created' => 'onCustomerCreated',
        'payment-method-created' => 'onPaymentMethodCreated',
    ];

    public function mount()
    {
        // Load data for selects
        $this->saleChannels = SaleChannel::where('is_active', true)->pluck('name', 'id')->toArray();

        $this->paymentMethods = PaymentMethod::where('is_active', true)->get()->mapWithKeys(function ($item) {
            return [$item->code => $item->name];
        })->toArray();

        $this->deliveryPartners = PaymentPartnerDelivery::where('is_active', true)->pluck('name', 'id')->toArray();
    }

    public function onSaleChannelCreated($id)
    {
        $this->saleChannels = SaleChannel::where('is_active', true)->pluck('name', 'id')->toArray();
        $this->sale_channel_id = $id;
    }

    public function onPartnerDeliveryCreated($id)
    {
        $this->deliveryPartners = PaymentPartnerDelivery::where('is_active', true)->pluck('name', 'id')->toArray();
        $this->partner_delivery_id = $id;
    }

    public function onCustomerCreated($customerId)
    {
        $this->customer_id = $customerId;
        $this->search_customer = '';
    }

    public function onPaymentMethodCreated($code)
    {
        $this->paymentMethods = PaymentMethod::where('is_active', true)->get()->mapWithKeys(function ($item) {
            return [$item->code => $item->name];
        })->toArray();
        $this->payment_method_code = $code;
    }

    public function selectCustomer($customerId)
    {
        $this->customer_id = $customerId;
        $this->search_customer = '';
    }

    public function removeCustomer()
    {
        $this->customer_id = null;
        $this->search_customer = '';
    }

    #[Computed]
    public function customers(): Customer|Collection
    {
        if (isset_value($this->search_customer)) {
            return Customer::select(['id', 'name', 'code', 'phone', 'email'])
            ->where(function ($q) {
                $q->where('name', 'like', "%{$this->search_customer}%")
                ->orWhere('phone', 'like', "%{$this->search_customer}%")
                ->orWhere('email', 'like', "%{$this->search_customer}%")
                ;
            })
            ->limit(50)
            ->get();
        }

        return new Collection();
    }

    #[Computed]
    public function customer(): Customer|Collection
    {
        if ($this->customer_id) {
            return Customer::select(['id', 'name', 'phone', 'address'])
            ->find($this->customer_id);
        }

        return new Collection();
    }

    public function show($id)
    {
        $this->resetValidation();
        $this->paymentId = $id;

        $payment = Payment::with('latestDelivery')->find($id);

        if (! $payment) {
            $this->dispatch('error', 'Không tìm thấy đơn hàng.');

            return;
        }

        $this->sale_channel_id = $payment->sale_channel_id;
        $this->customer_id = $payment->customer_id;

        // Parse Type Payment (JSON) to get Method Code
        $typePayment = $payment->type_payment;
        if (is_array($typePayment) && count($typePayment) > 0) {
            $first = $typePayment[0];
            $this->payment_method_code = is_array($first) ? ($first['method'] ?? '') : '';
        } else {
            $this->payment_method_code = '';
        }

        // Delivery Info
        $delivery = $payment->latestDelivery;
        $this->partner_delivery_id = $delivery ? $delivery->partner_delivery_id : null;
        $this->delivery_code = $delivery ? $delivery->code : '';

        // Open Modal
        $this->dispatch('open-modal-quick-update');
    }

    public function save()
    {
        $this->authorize('accountings.edit');

        $payment = Payment::find($this->paymentId);
        if (! $payment) {
            $this->dispatch('error', 'Không tìm thấy đơn hàng.');

            return;
        }

        // 1. Update Sale Channel & Customer
        $payment->sale_channel_id = $this->sale_channel_id ?: null;
        $payment->customer_id = $this->customer_id ?: null;

        // 2. Update Payment Method
        if ($this->payment_method_code) {
            $methodModel = PaymentMethod::where('code', $this->payment_method_code)->first();
            $methodName = $methodModel ? $methodModel->name : $this->payment_method_code;

            // Keep the current value (amount paid/to pay)
            $currentValue = 0;
            if (is_array($payment->type_payment) && count($payment->type_payment) > 0) {
                $currentValue = $payment->type_payment[0]['value'] ?? $payment->value;
            } else {
                $currentValue = $payment->value; // Fallback to total value
            }

            $payment->type_payment = [[
                'method' => $this->payment_method_code,
                'value' => (int)$currentValue,
                'label' => $methodName,
            ]];
        }

        // 3. Update Delivery Info (Partner & Code)
        if ($this->partner_delivery_id || $this->delivery_code) {
            $delivery = $payment->latestDelivery;

            if (! $delivery) {
                $delivery = new PaymentDelivery();
                $delivery->product_payment_id = $payment->id;
                $delivery->status = 'wait';
                $delivery->value = 0; // Default fee
                $delivery->save(); // Save first to get ID if needed, though product_payment_id is key
            }

            $delivery->partner_delivery_id = $this->partner_delivery_id ?: null;
            $delivery->code = $this->delivery_code;
            $delivery->save();

            // If partner is selected, mark order as delivery
            if ($this->partner_delivery_id) {
                $payment->is_delivery = 1;
            }
        }

        $payment->save();

        $this->dispatch('success', 'Cập nhật thông tin thành công.');
        $this->dispatch('refresh-datatable-product-payments'); // Refresh PowerGrid
        $this->dispatch('pg:eventRefresh-product-payment-table'); // Refresh PowerGrid v5 event
        $this->dispatch('hide-modal-quick-update'); // Close Modal
    }

    public function render()
    {
        return view('modules/accounting::payment.modal.quick-update');
    }
}
