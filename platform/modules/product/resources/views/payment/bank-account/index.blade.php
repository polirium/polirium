<x-ui.layouts::app>
    <x-slot:title>
        {{ __('Tài khoản ngân hàng (VietQR)') }}
    </x-slot:title>

    <x-slot:actions>
        <div class="btn-list">
            <x-ui::button href="{{ route('vietqr.index') }}" color="secondary" outline icon="qrcode">
                {{ __('Tạo QR thanh toán') }}
            </x-ui::button>
            <x-ui::button type="button" color="primary" icon="plus" onclick="Livewire.dispatch('modal-create-bank-account')">
                {{ __('Thêm tài khoản') }}
            </x-ui::button>
        </div>
    </x-slot:actions>

    <div class="row">
        <div class="col-12">
            <div class="alert alert-info">
                {{ __('Chỉ tài khoản user ID = 1 mới truy cập được trang cấu hình này. Gắn tài khoản vào phương thức thanh toán để hiện QR trên POS.') }}
            </div>
            @livewire('modules/product::payment.bank-account-table')
        </div>
    </div>

    @livewire('modules/product::payment.modal.modal-create-bank-account')
</x-ui.layouts::app>
