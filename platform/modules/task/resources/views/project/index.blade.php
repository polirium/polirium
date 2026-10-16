<x-ui.layouts::app>
    <x-slot:title>{{ __('modules/task::project.index') }}</x-slot:title>
    <x-slot:actions>
        @can('tasks.index')
            <a href="{{ route('admin.tasks.index') }}" class="btn btn-outline-secondary">
                {!! tabler_icon('checklist', ['class' => 'icon']) !!} {{ __('modules/task::task.index') }}
            </a>
        @endcan
        @can('projects.create')
            <a href="{{ route('admin.projects.create') }}" class="btn btn-primary">
                {!! tabler_icon('plus', ['class' => 'icon']) !!} {{ __('modules/task::project.create') }}
            </a>
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
