<x-ui.layouts::app>
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        {{ trans('modules/customer::customer.group.index') }}
                    </h2>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-3">
            @livewire('modules/customer::customer-group.filter-sidebar')
        </div>
        <div class="col-md-9">
            <x-ui::card>
                @livewire('modules/customer::customer-group-table')
            </x-ui::card>
        </div>
    </div>
</x-ui.layouts::app>
