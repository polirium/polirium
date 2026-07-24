<x-ui.layouts::minimal>
    <div class="page">
        <div class="container-sm py-3 py-md-4 px-3" style="max-width: 720px;">
            <div class="text-center mb-3 mb-md-4">
                <a href="{{ url('/') }}" class="navbar-brand navbar-brand-autodark d-inline-block mb-2">
                    <img src="{{ get_logo() }}" width="100" height="30" alt="Polirium" class="navbar-brand-image">
                </a>
                <h1 class="fs-3 mb-1">{{ __('QR thanh toán') }}</h1>
                <p class="text-secondary small mb-0">{{ __('Nhập số tiền và nội dung — mã QR cập nhật tự động') }}</p>
            </div>

            @livewire('modules/product::payment.vietqr-generator')
        </div>
    </div>
</x-ui.layouts::minimal>
