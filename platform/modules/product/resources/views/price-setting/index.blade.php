<x-ui.layouts::app>
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">{{ __('modules/product::product.price_list_title') }}</h2>
                    <div class="text-secondary mt-1">{{ __('modules/product::product.price_list_hint') }}</div>
                </div>
            </div>
        </div>
    </div>

    @livewire('modules/product::product-price-setting-table')
</x-ui.layouts::app>
