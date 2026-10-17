<x-ui.layouts::app>
    <x-slot:title>{{ __('modules/task::project.index') }}</x-slot:title>
    <x-slot:actions>
        @can('tasks.index')
            <x-ui::button color="secondary" outline icon="checklist" :href="route('admin.tasks.index')">
                {{ __('modules/task::task.index') }}
            </x-ui::button>
        @endcan
        @can('projects.create')
            <x-ui::button color="primary" icon="plus" :href="route('admin.projects.create')">
                {{ __('modules/task::project.create') }}
            </x-ui::button>
        @endcan
    </x-slot:actions>

    @include('modules/task::partials.alerts')

    <div class="row g-3">
        <div class="col-lg-3">
            @livewire('modules/task::project.filter-sidebar')
        </div>
        <div class="col-lg-9">
            <x-ui::card>
                @livewire('modules/task::project.datatable.project-table')
            </x-ui::card>
        </div>
    </div>
</x-ui.layouts::app>
