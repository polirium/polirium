<x-ui.layouts::app>
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        {{ __('modules/task::project.index') }}
                    </h2>
                </div>
                <div class="col-auto">
                    @can('projects.create')
                        <a href="{{ route('admin.projects.create') }}" class="btn btn-primary d-flex align-items-center gap-1">
                            {!! tabler_icon('plus', ['class' => 'icon']) !!}
                            {{ __('core/base::general.create') }}
                        </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="row">
                <div class="col-md-3">
                    @livewire('modules/task::project.filter-sidebar')
                </div>
                <div class="col-md-9">
                    <x-ui::card>
                        @livewire('modules/task::project.datatable.project-table')
                    </x-ui::card>
                </div>
            </div>
        </div>
    </div>
</x-ui.layouts::app>
