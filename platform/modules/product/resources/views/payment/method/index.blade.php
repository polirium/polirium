<x-ui.layouts::app>
    <x-slot:title>
        {{ trans('modules/product::product.payment_method') }}
    </x-slot:title>

    <x-slot:actions>
        @can('products.payment-method.edit')
        <button class="btn btn-primary" onclick="Livewire.dispatch('modal-create-payment-method')">
            {!! tabler_icon('plus') !!} {{ __('Thêm mới') }}
        </button>
        @endcan
    </x-slot:actions>

    <div class="row">
        <div class="col-12">
            @livewire('modules/product::payment.payment-method-table')
        </div>
    </div>

    @livewire('modules/product::payment.modal.modal-create-payment-method')
</x-ui.layouts::app>


