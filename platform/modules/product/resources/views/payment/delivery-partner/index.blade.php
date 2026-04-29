<x-ui.layouts::app>
    <x-slot:title>
        {{ trans('modules/product::product.delivery_partner.label') }}
    </x-slot:title>

    <div class="row">
        <div class="col-12">
            @livewire('modules/product::payment.delivery-partner-table')
        </div>
    </div>

    @livewire('modules/product::payment.modal.modal-create-delivery-partner')
</x-ui.layouts::app>
