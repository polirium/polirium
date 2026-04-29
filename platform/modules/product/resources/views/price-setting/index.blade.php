<x-ui.layouts::app>
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        Thiết lập giá sản phẩm theo chi nhánh
                    </h2>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-3">
            @livewire('modules/product::price-setting.filter-sidebar')
        </div>
        <div class="col-md-9">
            <x-ui::card>
                @livewire('modules/product::product-price-setting-table')
            </x-ui::card>
        </div>
    </div>
</x-ui.layouts::app>
