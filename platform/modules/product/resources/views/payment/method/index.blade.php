<x-ui.layouts::app>
    <x-slot:title>
        {{ trans('modules/product::product.payment_method') }}
    </x-slot:title>

    <x-slot:actions>
        @if ((int) auth()->id() === 1)
            <a href="{{ route('products.bank-accounts.index') }}" class="btn btn-outline-primary me-2">
                {!! tabler_icon('building-bank') !!} {{ __('Tài khoản NH / VietQR') }}
            </a>
        @endif
        <a href="{{ route('vietqr.index') }}" class="btn btn-outline-secondary me-2">
            {!! tabler_icon('qrcode') !!} {{ __('Tạo QR') }}
        </a>
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


