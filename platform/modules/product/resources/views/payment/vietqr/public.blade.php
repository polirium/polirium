<x-ui.layouts::minimal>
    <div class="page page-center">
        <div class="container-xl py-4">
            <div class="text-center mb-4">
                <a href="{{ url('/') }}" class="navbar-brand navbar-brand-autodark">
                    <img src="{{ get_logo() }}" width="110" height="32" alt="Polirium" class="navbar-brand-image">
                </a>
                <h2 class="mt-3 mb-1">{{ __('Tạo QR thanh toán VietQR') }}</h2>
                <p class="text-secondary mb-0">{{ __('Chọn tài khoản, nhập số tiền và nội dung để tạo mã QR') }}</p>
            </div>

            @livewire('modules/product::payment.vietqr-generator')
        </div>
    </div>
</x-ui.layouts::minimal>
