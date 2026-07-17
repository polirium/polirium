<x-ui.layouts::app>
    <x-slot:title>
        {{ __('Tài khoản ngân hàng (VietQR)') }}
    </x-slot:title>

    <x-slot:actions>
        <a href="{{ route('vietqr.index') }}" class="btn btn-outline-primary me-2">
            {!! tabler_icon('qrcode') !!} {{ __('Tạo QR thanh toán') }}
        </a>
        <button class="btn btn-primary" onclick="Livewire.dispatch('modal-create-bank-account')">
            {!! tabler_icon('plus') !!} {{ __('Thêm tài khoản') }}
        </button>
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
