<x-ui.layouts::app>
    <x-slot:title>
        {{ trans('modules/product::product.sale_channel.label') }}
    </x-slot:title>

    <div class="row">
        <div class="col-12">
            @livewire('modules/product::payment.sale-channel-table')
        </div>
    </div>

    @livewire('modules/product::payment.modal.modal-create-sale-channel')
</x-ui.layouts::app>
