<x-ui.layouts::app>
    <x-slot:title>{{ __('modules/task::task.index') }}</x-slot:title>
    <x-slot:actions>
        @include('modules/task::partials.view-switcher')
    </x-slot:actions>

    @include('modules/task::partials.alerts')

    <div class="row g-3">
        <div class="col-lg-3">
            @livewire('modules/task::task.filter-sidebar')
        </div>
        <div class="col-lg-9">
            <x-ui::card>
                @livewire('modules/task::task.datatable.task-table')
            </x-ui::card>
        </div>
    </div>
</x-ui.layouts::app>
