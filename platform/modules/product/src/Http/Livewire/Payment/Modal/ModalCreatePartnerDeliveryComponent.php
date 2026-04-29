<?php

namespace Polirium\Modules\Product\Http\Livewire\Payment\Modal;

use Livewire\Attributes\On;
use Livewire\Component;
use Polirium\Modules\Product\Http\Model\Payment\PaymentPartnerDelivery;

class ModalCreatePartnerDeliveryComponent extends Component
{
    public array $list = [
        'provinces' => [],
        'districts' => [],
        'wards' => [],
    ];

    public ?string $partner_delivery_id = null;

    public array $input = [];

    protected function rules()
    {
        return [
            'input.type' => ['required', 'string', 'max:255', 'in:person,company'],
            'input.name' => ['required', 'string', 'max:255'],
            'input.code' => ['required', 'string', 'max:255', 'unique:product_payment_partner_deliveries,code,' . ($this->partner_delivery_id ?? 'NULL') . ',id'],
            'input.address' => ['nullable', 'string', 'max:255'],
            'input.phone' => ['nullable', 'string', 'max:255'],
            'input.email' => ['nullable', 'string', 'max:255', 'email'],
            'input.province_id' => ['nullable', 'numeric', 'integer'],
            'input.district_id' => ['nullable', 'numeric', 'integer'],
            'input.ward_id' => ['nullable', 'numeric', 'integer'],
            'input.note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function mount()
    {
        $this->list['provinces'] = get_provinces();

        $this->resetInputs();
    }

    public function updatedInput($value, $key)
    {
        if ($key == 'province_id') {
            if ($value) {
                $this->list['districts'] = get_districts($value);
                $this->list['wards'] = [];
            } else {
                $this->list['districts'] = [];
                $this->list['wards'] = [];
            }

            $this->input['district_id'] = null;
            $this->input['ward_id'] = null;
        } elseif ($key == 'district_id') {
            if ($value) {
                $this->list['wards'] = get_wards($value);
            } else {
                $this->list['wards'] = [];
            }

            $this->input['ward_id'] = null;
        }
    }

    public function render()
    {
        return view('modules/product::payment.modal.modal-create-partner-delivery');
    }

    public function resetInputs()
    {
        $this->input = [
            'type' => 'person',
            'code' => code_generate('ĐTGH', PaymentPartnerDelivery::max('id')),
            'name' => null,
            'address' => null,
            'phone' => null,
            'email' => null,
            'province_id' => null,
            'district_id' => null,
            'ward_id' => null,
            'note' => null,
        ];
    }

    #[On('show-modal-create-partner-delivery')]
    public function showModal(int $id = null): void
    {
        $this->authorize($id ? 'products.delivery-partner.edit' : 'products.delivery-partner.create');

        $this->partner_delivery_id = $id;
        if ($id) {
            $this->input = PaymentPartnerDelivery::findOrFail($id)->toArray();
        } else {
            $this->resetInputs();
        }
        $this->dispatch('modal', 'modal-create-partner-delivery');
    }

    public function save(): void
    {
        $this->authorize($this->partner_delivery_id ? 'products.delivery-partner.edit' : 'products.delivery-partner.create');

        if (empty($this->input['province_id'])) {
            $this->input['province_id'] = null;
        }
        if (empty($this->input['district_id'])) {
            $this->input['district_id'] = null;
        }
        if (empty($this->input['ward_id'])) {
            $this->input['ward_id'] = null;
        }

        $this->validate();

        if ($this->partner_delivery_id) {
            PaymentPartnerDelivery::find($this->partner_delivery_id)->update($this->input);
        } else {
            PaymentPartnerDelivery::create($this->input);
        }

        $this->dispatch('modal', 'modal-create-partner-delivery', 'hide');
        $this->dispatch('refresh-payment');
        $this->dispatch('payment-refresh-list');
        $this->dispatch('partner-delivery-created', id: $this->partner_delivery_id ? $this->partner_delivery_id : \Polirium\Modules\Product\Http\Model\Payment\PaymentPartnerDelivery::max('id'));
    }
}
