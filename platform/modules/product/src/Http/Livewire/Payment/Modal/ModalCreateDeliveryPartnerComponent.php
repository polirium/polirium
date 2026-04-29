<?php

namespace Polirium\Modules\Product\Http\Livewire\Payment\Modal;

use Livewire\Attributes\On;
use Livewire\Component;
use Polirium\Modules\Product\Http\Model\Payment\PaymentPartnerDelivery;

class ModalCreateDeliveryPartnerComponent extends Component
{
    public ?string $partner_id = null;

    public array $input = [
        'name' => '',
        'code' => '',
        'phone' => '',
        'email' => '',
        'address' => '',
        'note' => '',
    ];

    protected function rules()
    {
        return [
            'input.name' => 'required|string|max:255',
            'input.code' => "nullable|string|max:255|unique:product_payment_partner_deliveries,code,{$this->partner_id},id",
            'input.phone' => 'nullable|string|max:20',
            'input.email' => 'nullable|email|max:255',
            'input.address' => 'nullable|string|max:255',
            'input.note' => 'nullable|string',
        ];
    }

    public function mount()
    {
        $this->input = $this->getDefaultInput();
    }

    private function getDefaultInput(): array
    {
        return [
            'name' => '',
            'code' => '',
            'phone' => '',
            'email' => '',
            'address' => '',
            'note' => '',
        ];
    }

    public function render()
    {
        return view('modules/product::payment.modal.modal-create-delivery-partner');
    }

    #[On('show-modal-create-delivery-partner')]
    public function showModal(int $id = null): void
    {
        $this->authorize($id ? 'products.delivery-partner.edit' : 'products.delivery-partner.create');

        $this->partner_id = $id;
        if ($id) {
            $model = PaymentPartnerDelivery::findOrFail($id);
            $this->input = $model->toArray();
        } else {
            $this->input = $this->getDefaultInput();
        }
        $this->dispatch('modal', 'modal-create-delivery-partner');
    }

    public function save(): void
    {
        $this->authorize($this->partner_id ? 'products.delivery-partner.edit' : 'products.delivery-partner.create');

        $this->validate();

        if ($this->partner_id) {
            $model = PaymentPartnerDelivery::findOrFail($this->partner_id);
            $model->update($this->input);
        } else {
            $model = PaymentPartnerDelivery::create($this->input);
        }

        $this->dispatch('modal', 'modal-create-delivery-partner', 'hide');
        $this->dispatch('delivery-partner-created');
        $this->dispatch('refresh-datatable-delivery-partner');
    }
}
