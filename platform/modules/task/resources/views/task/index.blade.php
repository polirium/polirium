<x-ui.layouts::app>
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        {{ __('modules/task::task.index') }}
                    </h2>
                </div>
                <div class="col-auto">
                    <div class="btn-list">
                        @can('tasks.kanban')
                            <a href="{{ route('admin.tasks.kanban') }}" class="btn btn-white">
                                {!! tabler_icon('columns', ['class' => 'icon']) !!}
                                {{ __('modules/task::task.kanban') }}
                            </a>
                        @endcan
                        @can('tasks.gantt')
                            <a href="{{ route('admin.tasks.gantt') }}" class="btn btn-white">
                                {!! tabler_icon('chart-bar', ['class' => 'icon']) !!}
                                {{ __('modules/task::task.gantt') }}
                            </a>
                        @endcan
                        @can('tasks.create')
                            <a href="{{ route('admin.tasks.create') }}" class="btn btn-primary d-flex align-items-center gap-1">
                                {!! tabler_icon('plus', ['class' => 'icon']) !!}
                                {{ __('core/base::general.create') }}
                            </a>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="row">
                <div class="col-md-3">
                    @livewire('modules/task::task.filter-sidebar')
                </div>
                <div class="col-md-9">
                    <x-ui::card>
                        @livewire('modules/task::task.datatable.task-table')
                    </x-ui::card>
                </div>
            </div>
        </div>
    </div>
</x-ui.layouts::app>
