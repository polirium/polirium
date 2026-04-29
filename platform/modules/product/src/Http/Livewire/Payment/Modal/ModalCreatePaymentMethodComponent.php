<?php

namespace Polirium\Modules\Product\Http\Livewire\Payment\Modal;

use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Polirium\Modules\Product\Http\Model\Payment\PaymentMethod;

class ModalCreatePaymentMethodComponent extends Component
{
    public ?PaymentMethod $paymentMethod = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|string|max:255|unique:payment_methods,code')]
    public string $code = '';

    #[Validate('nullable|string')]
    public ?string $description = '';

    #[Validate('boolean')]
    public bool $is_active = true;

    #[Validate('boolean')]
    public bool $is_default = false;

    #[Validate('required|in:completed,pending')]
    public string $target_payment_status = PaymentMethod::STATUS_COMPLETED;

    #[On('modal-create-payment-method')]
    public function open($id = null)
    {
        if (is_array($id)) {
            $id = $id['id'] ?? null;
        }

        $this->authorize($id ? 'products.payment-method.edit' : 'products.payment-method.create');

        $this->ResetInputs();

        if ($id) {
            $this->paymentMethod = PaymentMethod::find($id);
            if ($this->paymentMethod) {
                $this->name = $this->paymentMethod->name;
                $this->code = $this->paymentMethod->code;
                $this->description = $this->paymentMethod->description;
                $this->is_active = $this->paymentMethod->is_active;
                $this->is_default = $this->paymentMethod->is_default;
                $this->target_payment_status = $this->paymentMethod->target_payment_status ?? PaymentMethod::STATUS_COMPLETED;
            }
        }

        $this->dispatch('modal', 'modal-create-payment-method');
    }

    public function mount()
    {
        $this->ResetInputs();
    }

    public function render()
    {
        return view('modules/product::payment.modal.modal-create-payment-method');
    }

    public function save()
    {
        $this->authorize($this->paymentMethod ? 'products.payment-method.edit' : 'products.payment-method.create');

        $this->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:payment_methods,code,' . ($this->paymentMethod?->id),
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'target_payment_status' => 'required|in:completed,pending',
        ]);

        if ($this->is_default) {
            PaymentMethod::where('is_default', true)->update(['is_default' => false]);
        }

        PaymentMethod::updateOrCreate(
            ['id' => $this->paymentMethod?->id],
            [
                'name' => $this->name,
                'code' => $this->code,
                'description' => $this->description,
                'is_active' => $this->is_active,
                'is_default' => $this->is_default,
                'target_payment_status' => $this->target_payment_status,
            ]
        );

        $this->dispatch('pg:eventRefresh-product-payment-method-table');
        $this->dispatch('modal', 'modal-create-payment-method', 'hide');
        $this->dispatch('payment-method-created', code: $this->code);
        $this->ResetInputs();
    }

    public function ResetInputs()
    {
        $this->paymentMethod = null;
        $this->name = '';
        $this->code = '';
        $this->description = '';
        $this->is_active = true;
        $this->is_default = false;
        $this->target_payment_status = PaymentMethod::STATUS_COMPLETED;
        $this->resetErrorBag();
    }
}
