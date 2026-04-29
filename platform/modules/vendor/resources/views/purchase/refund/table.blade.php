<x-ui.layouts::app>
    <div class="row">
        <div class="col-md-3">
            @livewire('modules/vendor::purchase.refund.filter-sidebar')
        </div>
        <div class="col-md-9">
            <x-ui::card>
                @livewire('modules/vendor::purchase-refund-table')
            </x-ui::card>
        </div>
    </div>
</x-ui.layouts::app>
