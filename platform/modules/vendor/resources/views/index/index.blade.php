<x-ui.layouts::app>
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        {{ trans('modules/vendor::vendor.index') }}
                    </h2>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-3">
            <x-ui::card>
                @livewire('modules/vendor::index.search-sidebar')
            </x-ui::card>
        </div>
        <div class="col-md-9">
            @livewire('modules/vendor::vendor-table')
        </div>
    </div>

    @livewire('modules/vendor::index.modal.modal-create-vendor')
</x-ui.layouts::app>
