<?php

namespace Polirium\Modules\Product\Http\Livewire\Payment\Modal;

use Livewire\Attributes\On;
use Livewire\Component;
use Polirium\Modules\Product\Http\Model\Payment\SaleChannel;

class ModalCreateSaleChannelComponent extends Component
{
    public ?string $sale_channel_id = null;

    public array $input = [
        'name' => '',
        'description' => '',
    ];

    protected function rules()
    {
        return [
            'input.name' => "required|string|max:255|unique:product_payment_sale_channels,name,{$this->sale_channel_id},id",
            'input.description' => 'nullable|string|max:255',
        ];
    }

    public function mount()
    {
        $this->input = ['name' => '', 'description' => ''];
    }

    public function render()
    {
        return view('modules/product::payment.modal.modal-create-sale-channel');
    }

    #[On('show-modal-create-sale-channel')]
    public function showModal(int $id = null): void
    {
        $this->authorize($id ? 'products.sale-channel.edit' : 'products.sale-channel.create');

        $this->sale_channel_id = $id;
        if ($id) {
            $model = SaleChannel::findOrFail($id);
            $this->input = $model->toArray();
        } else {
            $this->input = ['name' => '', 'description' => ''];
        }
        $this->dispatch('modal', 'modal-create-sale-channel');
    }

    public function save(): void
    {
        $this->authorize($this->sale_channel_id ? 'products.sale-channel.edit' : 'products.sale-channel.create');

        $this->validate();

        if ($this->sale_channel_id) {
            $model = SaleChannel::findOrFail($this->sale_channel_id);
            $model->update($this->input);
        } else {
            $model = SaleChannel::create($this->input);
        }

        $this->dispatch('modal', 'modal-create-sale-channel', 'hide');
        $this->dispatch('refresh-payment');
        $this->dispatch('payment-refresh-list');
        $this->dispatch('sale-channel-created', id: $model->id);

        // Dispatch global update for UI (bypassing specific component round-trip)
        $channels = SaleChannel::all()->map(function ($channel) {
            return ['value' => (string)$channel->id, 'text' => $channel->name];
        })->values()->all();
        $this->dispatch('global-update-sale-channels', options: $channels, selected: $model->id);
    }
}
