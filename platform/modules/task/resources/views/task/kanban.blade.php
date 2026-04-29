<x-ui.layouts::app>
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        {{ __('modules/task::task.kanban') }}
                    </h2>
                </div>
                <div class="col-auto">
                    <div class="btn-list">
                        <a href="{{ route('admin.tasks.index') }}" class="btn btn-white">
                            {!! tabler_icon('list', ['class' => 'icon']) !!}
                            {{ __('modules/task::task.index') }}
                        </a>
                        <a href="{{ route('admin.tasks.gantt') }}" class="btn btn-white">
                            {!! tabler_icon('chart-bar', ['class' => 'icon']) !!}
                            {{ __('modules/task::task.gantt') }}
                        </a>
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
            @livewire('modules/task::task.kanban')
        </div>
    </div>
</x-ui.layouts::app>
