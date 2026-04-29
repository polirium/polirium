<?php

namespace Polirium\Modules\Product\Http\Livewire\Payment\Modal;

use Livewire\Attributes\On;
use Livewire\Component;
use Polirium\Core\UI\Facades\Assets;
use Polirium\Modules\Product\Http\Model\Payment\PaymentMethod;

class ModalPaymentTypeComponent extends Component
{
    public string|null $tab_selected = null;

    public int $fixed_value = 0;

    public int $value = 0;

    public array $payment_methods_list = [];

    // FIX: Add payment_methods property for displaying available payment methods in modal
    public array $payment_methods = [];

    public function mount(): void
    {
        Assets::loadCss('professional-table');

        // FIX: Initialize payment_methods from database
        // This is needed for the modal to display available payment methods
        $this->payment_methods = PaymentMethod::where('is_active', true)
            ->get()
            ->toArray();
    }

    public function updatedValue($value)
    {
        if ($value <= 0) {
            $this->value = 0;

            return;
        }

        if (collect($this->payment_methods_list)->sum('value') === $this->fixed_value) {
            $this->value = 0;

            return;
        }

        if ($value < 0 || ($value > $this->fixed_value)) {
            $this->value = $this->fixed_value - collect($this->payment_methods_list)->sum('value');
        }
    }

    public function render()
    {
        return view('modules/product::payment.modal.modal-payment-type');
    }

    /**
     * @param int $value Số tiền chuyền vào
     * @param string|null $tab_selected Tab hiển thị ở mục bán hàng
     * @return void
     */
    #[On('show-modal-payment-type-{tab_selected}')]
    public function showModal(int $value, ?string $tab_selected = null, array $payment_methods = []): void
    {
        $this->fixed_value = $value;
        $this->payment_methods_list = [];

        // Normalize payment methods if they are just strings (e.g. ['cash'])
        if (! empty($payment_methods)) {
            foreach ($payment_methods as $item) {
                if (is_string($item)) {
                    // Just add it to the list, DON'T AUTO SELECT if value is 0
                    // But wait, the previous logic was calling selectMethod which calculates value
                    // The issue is likely that when we initialize, we shouldn't default to adding 'cash' with 0 value
                    // UNLESS it actually has value.
                    // However, in PaymentComponent resetInputs(), we default to:
                    // 'type_payment' => [['method' => 'cash', 'value' => 0, ...]]

                    // So we need to filter out methods with 0 value unless it's the only method and we want to show it?
                    // Actually, the user complained about "Cash 0".

                    // Let's filter out methods with 0 value when loading into modal
                    // BUT, if it's the ONLY method and user hasn't paid anything yet, maybe we shouldn't add it at all?

                    // Let's just skip adding it here if it's a string (legacy/default behavior)
                    // If it's an array, we trust the value passed in.

                    // Actually, if it's a string, it means it came from the default setPaymentMethod logic or resetInputs
                    // which might default to 'cash' without value.

                    // Let's change the logic: Only add to payment_methods_list if value > 0
                    // OR if it's explicitly passed as an array with value > 0.

                    // If it is a string (e.g. 'cash'), it implies we should probably ignore it if we are just opening the modal
                    // to let the user choose. But if they clicked "Cash" on the main screen, they expect it to be selected?
                    // The main screen buttons call setPaymentMethod('cash') which sets value_payment (default 0 or total).

                    // If value_payment is 0, then we have Cash: 0.
                    // We should filter out any payment method with value <= 0 from the list displayed in the modal.

                } elseif (is_array($item) && isset($item['method'], $item['value'])) {
                    if ((int)$item['value'] > 0) {
                        $this->payment_methods_list[] = $item;
                    }
                }
            }
        }

        $this->value = $value - collect($this->payment_methods_list)->sum('value');
        if ($this->value < 0) {
            $this->value = 0;
        }

        if ($tab_selected) {
            $this->tab_selected = $tab_selected;
        }
        $this->dispatch('modal', "modal-payment-type-{$this->tab_selected}");
    }

    public function selectMethod($method): void
    {
        if ($this->value <= 0) {
            $this->value = 0;

            return;
        }

        $remain = $this->fixed_value - collect($this->payment_methods_list)->sum('value');

        if ($this->value > $this->fixed_value) {
            $this->value = $remain;

            return;
        }

        if ($this->value > $remain) {
            $this->value = $remain;

            return;
        }

        $label = match ($method) {
            'cash' => 'Tiền mặt',
            'bank' => 'Chuyển khoản',
            'card' => 'Thẻ',
            'cod' => 'COD',
            'other' => 'Khác',
            default => $method,
        };

        $this->payment_methods_list[] = [
            'value' => $this->value,
            'method' => $method,
            'label' => $label,
        ];

        $this->value = $this->fixed_value - collect($this->payment_methods_list)->sum('value');
        if ($this->value < 0) {
            $this->value = 0;
        }
    }

    public function removeMethod($key): void
    {
        unset($this->payment_methods_list[$key]);
        $this->value = $this->fixed_value - collect($this->payment_methods_list)->sum('value');
    }

    public function save(): void
    {
        $this->dispatch("payment-methods-value-{$this->tab_selected}", $this->payment_methods_list);
        $this->dispatch('modal', "modal-payment-type-{$this->tab_selected}", 'hide');
    }
}
