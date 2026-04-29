<x-ui.layouts::app>
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        {{ trans('modules/customer::customer.index') }}
                    </h2>
                </div>

            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-3">
            @livewire('modules/customer::index.filter')
        </div>
        <div class="col-md-9">
            @livewire('modules/customer::customer-table')
        </div>
    </div>

    @livewire('modules/customer::index.modal.modal-create-customer')
    @livewire('modules/customer::index.modal.modal-import-customer')
    @livewire('modules/customer::index.modal.modal-create-customer-group')
</x-ui.layouts::app>
