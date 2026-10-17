<x-ui.layouts::app>
    <x-slot:title>
        {{ trans('modules/product::product.payment_method') }}
    </x-slot:title>

    <x-slot:actions>
        <div class="btn-list">
            @if ((int) auth()->id() === 1)
                <x-ui::button href="{{ route('products.bank-accounts.index') }}" color="secondary" outline icon="building-bank">
                    {{ __('Tài khoản NH / VietQR') }}
                </x-ui::button>
            @endif
            <x-ui::button href="{{ route('vietqr.index') }}" color="secondary" outline icon="qrcode">
                {{ __('Tạo QR') }}
            </x-ui::button>
            @can('products.payment-method.edit')
            <x-ui::button type="button" color="primary" icon="plus" onclick="Livewire.dispatch('modal-create-payment-method')">
                {{ __('Thêm phương thức thanh toán') }}
            </x-ui::button>
            @endcan
        </div>
    </x-slot:actions>

    <div class="row">
        <div class="col-12">
            @livewire('modules/product::payment.payment-method-table')
        </div>
    </div>

    @livewire('modules/product::payment.modal.modal-create-payment-method')
</x-ui.layouts::app>


