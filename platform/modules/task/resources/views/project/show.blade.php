<x-ui.layouts::app>
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">
                        {{ __('modules/task::project.show') }}
                    </div>
                    <h2 class="page-title">{{ $project->name }}</h2>
                </div>
                <div class="d-print-none col-auto ms-auto">
                    <div class="btn-list">
                        <a href="{{ route('admin.projects.index') }}" class="btn btn-outline-secondary">
                            {!! tabler_icon('arrow-left', ['class' => 'icon']) !!}
                            {{ __('core/base::general.back') }}
                        </a>
                        @can('projects.edit')
                            <a href="{{ route('admin.projects.edit', $project->id) }}" class="btn btn-primary">
                                {!! tabler_icon('edit', ['class' => 'icon']) !!}
                                {{ __('core/base::general.edit') }}
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
                {{-- Project Info --}}
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">{{ trans('modules/task::task.project_info') }}</h3>
                        </div>
                        <div class="card-body">
                            <dl class="row">
                                <dt class="col-5">{{ trans('modules/task::task.project_code') }}</dt>
                                <dd class="col-7"><code>{{ $project->code }}</code></dd>

                                <dt class="col-5">{{ trans('modules/task::task.status') }}</dt>
                                <dd class="col-7">{{ $project->status_label }}</dd>

                                <dt class="col-5">{{ trans('modules/task::task.priority') }}</dt>
                                <dd class="col-7">{{ $project->priority_label }}</dd>

                                <dt class="col-5">{{ trans('modules/task::task.budget') }}</dt>
                                <dd class="col-7">{{ core_number_format($project->budget) }}</dd>

                                <dt class="col-5">{{ trans('modules/task::task.progress') }}</dt>
                                <dd class="col-7">
                                    <div class="progress progress-sm">
                                        <div class="progress-bar" style="width: {{ $project->progress_percentage }}%"></div>
                                    </div>
                                    <small>{{ $project->progress_percentage }}%</small>
                                </dd>

                                @if ($project->planned_start_date)
                                    <dt class="col-5">{{ trans('modules/task::task.start_date') }}</dt>
                                    <dd class="col-7">{{ $project->planned_start_date->format('d/m/Y') }}</dd>
                                @endif

                                @if ($project->planned_end_date)
                                    <dt class="col-5">{{ trans('modules/task::task.end_date') }}</dt>
                                    <dd class="col-7">{{ $project->planned_end_date->format('d/m/Y') }}</dd>
                                @endif

                                @if ($project->branch)
                                    <dt class="col-5">{{ trans('modules/task::task.branch') }}</dt>
                                    <dd class="col-7">{{ $project->branch->name }}</dd>
                                @endif

                                <dt class="col-5">{{ trans('modules/task::task.creator') }}</dt>
                                <dd class="col-7">{{ $project->createdBy?->name ?? '-' }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>

                {{-- Description --}}
                <div class="col-md-8">
                    <div class="card mb-3">
                        <div class="card-header">
                            <h3 class="card-title">{{ trans('modules/task::task.description') }}</h3>
                        </div>
                        <div class="card-body">
                            @if ($project->description)
                                {!! nl2br(e($project->description)) !!}
                            @else
                                <p class="text-muted">{{ trans('modules/task::task.no_description') }}</p>
                            @endif
                        </div>
                    </div>

                    {{-- Tasks List --}}
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">{{ __('modules/task::task.index') }}</h3>
                            <div class="card-actions">
                                <a href="{{ route('admin.tasks.index') }}?project_id={{ $project->id }}" class="btn btn-sm btn-primary">
                                    {!! tabler_icon('list', ['class' => 'icon']) !!}
                                    {{ trans('modules/task::task.view_all') }}
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            @if ($project->tasks->count() > 0)
                                <div class="list-group list-group-flush">
                                    @foreach ($project->tasks->take(10) as $task)
                                        <div class="list-group-item">
                                            <div class="row align-items-center">
                                                <div class="col-auto">
                                                    <span class="badge bg-{{ $task->status === 'done' ? 'success' : 'primary' }}">
                                                        {{ $task->status_label }}
                                                    </span>
                                                </div>
                                                <div class="col">
                                                    <a href="{{ route('admin.tasks.show', $task->id) }}"
                                                       class="text-decoration-none">
                                                        {{ $task->name }}
                                                    </a>
                                                    @if ($task->parent_id)
                                                        <small class="text-muted">
                                                            ({{ $task->parent?->name }})
                                                        </small>
                                                    @endif
                                                </div>
                                                <div class="col-auto">
                                                    <small class="text-muted">
                                                        {{ $task->progress_percentage }}%
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                @if ($project->tasks->count() > 10)
                                    <div class="mt-3 text-center">
                                        <a href="{{ route('admin.tasks.index') }}?project_id={{ $project->id }}"
                                           class="btn btn-outline-secondary btn-sm">
                                            {{ trans('modules/task::task.view_more') }} ({{ $project->tasks->count() - 10 }} {{ trans('modules/task::task.task_word') }})
                                        </a>
                                    </div>
                                @endif
                            @else
                                <p class="text-muted">{{ trans('modules/task::task.no_tasks') }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-ui.layouts::app>
