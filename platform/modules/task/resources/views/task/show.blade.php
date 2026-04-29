<x-ui.layouts::app>
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">
                        {{ __('modules/task::task.show') }}
                    </div>
                    <h2 class="page-title">{{ $task->name }}</h2>
                </div>
                <div class="d-print-none col-auto ms-auto">
                    <div class="btn-list">
                        <a href="{{ route('admin.tasks.index') }}" class="btn btn-outline-secondary">
                            {!! tabler_icon('arrow-left', ['class' => 'icon']) !!}
                            {{ __('core/base::general.back') }}
                        </a>
                        @can('tasks.edit')
                            <a href="{{ route('admin.tasks.edit', $task->id) }}" class="btn btn-primary">
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
                {{-- Task Info --}}
                <div class="col-md-4">
                    <div class="card mb-3">
                        <div class="card-header">
                            <h3 class="card-title">{{ trans('modules/task::task.task_info') }}</h3>
                        </div>
                        <div class="card-body">
                            <dl class="row">
                                <dt class="col-5">{{ trans('modules/task::task.task_code') }}</dt>
                                <dd class="col-7"><code>{{ $task->code }}</code></dd>

                                <dt class="col-5">{{ trans('modules/task::task.project') }}</dt>
                                @if ($task->project)
                                    <dd class="col-7">
                                        <a href="{{ route('admin.projects.show', $task->project->id) }}"
                                           class="text-decoration-none">
                                            {{ $task->project->name }}
                                        </a>
                                    </dd>
                                @else
                                    <dd class="col-7">-</dd>
                                @endif

                                <dt class="col-5">{{ trans('modules/task::task.status') }}</dt>
                                <dd class="col-7">{{ $task->status_label }}</dd>

                                <dt class="col-5">{{ trans('modules/task::task.priority') }}</dt>
                                <dd class="col-7">{{ $task->priority_label }}</dd>

                                @if ($task->parent)
                                    <dt class="col-5">{{ trans('modules/task::task.parent_task') }}</dt>
                                    <dd class="col-7">
                                        <a href="{{ route('admin.tasks.show', $task->parent->id) }}"
                                           class="text-decoration-none">
                                            {{ $task->parent->name }}
                                        </a>
                                    </dd>
                                @endif

                                <dt class="col-5">{{ trans('modules/task::task.assignee') }}</dt>
                                <dd class="col-7">{{ $task->assignedTo?->name ?? trans('modules/task::task.unassigned') }}</dd>

                                <dt class="col-5">{{ trans('modules/task::task.progress') }}</dt>
                                <dd class="col-7">
                                    <div class="progress progress-sm mb-1">
                                        <div class="progress-bar" style="width: {{ $task->progress_percentage }}%"></div>
                                    </div>
                                    <small>{{ $task->progress_percentage }}%</small>
                                </dd>

                                <dt class="col-5">{{ trans('modules/task::task.time') }}</dt>
                                <dd class="col-7">{{ $task->actual_hours }}/{{ $task->estimated_hours }}h</dd>

                                @if ($task->is_overdue)
                                    <dt class="col-5 text-danger">{{ trans('modules/task::task.status') }}</dt>
                                    <dd class="col-7">
                                        <span class="badge bg-danger">{{ __('modules/task::task.overdue') }}</span>
                                    </dd>
                                @endif
                            </dl>
                        </div>
                    </div>

                    {{-- Dates --}}
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">{{ trans('modules/task::task.time') }}</h3>
                        </div>
                        <div class="card-body">
                            <dl class="row">
                                @if ($task->planned_start_date)
                                    <dt class="col-7">{{ trans('modules/task::task.start_date') }}</dt>
                                    <dd class="col-5">{{ $task->planned_start_date->format('d/m/Y') }}</dd>
                                @endif

                                @if ($task->planned_end_date)
                                    <dt class="col-7">{{ trans('modules/task::task.end_date') }}</dt>
                                    <dd class="col-5">{{ $task->planned_end_date->format('d/m/Y') }}</dd>
                                @endif

                                @if ($task->actual_start_date)
                                    <dt class="col-7">{{ trans('modules/task::task.actual_start') }}</dt>
                                    <dd class="col-5">{{ $task->actual_start_date->format('d/m/Y') }}</dd>
                                @endif

                                @if ($task->actual_end_date)
                                    <dt class="col-7">{{ trans('modules/task::task.actual_end') }}</dt>
                                    <dd class="col-5">{{ $task->actual_end_date->format('d/m/Y') }}</dd>
                                @endif
                            </dl>
                        </div>
                    </div>
                </div>

                {{-- Description & Children --}}
                <div class="col-md-8">
                    <div class="card mb-3">
                        <div class="card-header">
                            <h3 class="card-title">{{ trans('modules/task::task.description') }}</h3>
                        </div>
                        <div class="card-body">
                            @if ($task->description)
                                {!! nl2br(e($task->description)) !!}
                            @else
                                <p class="text-muted">{{ trans('modules/task::task.no_description') }}</p>
                            @endif
                        </div>
                    </div>

                    {{-- Subtasks --}}
                    @if ($task->children->count() > 0)
                        <div class="card mb-3">
                            <div class="card-header">
                                <h3 class="card-title">{{ trans('modules/task::task.child_tasks') }}</h3>
                                <span class="badge bg-primary">{{ $task->children->count() }}</span>
                            </div>
                            <div class="card-body">
                                <div class="list-group list-group-flush">
                                    @foreach ($task->children as $child)
                                        <div class="list-group-item">
                                            <div class="row align-items-center">
                                                <div class="col-auto">
                                                    <span class="badge bg-{{ $child->status === 'done' ? 'success' : 'primary' }}">
                                                        {{ $child->status_label }}
                                                    </span>
                                                </div>
                                                <div class="col">
                                                    <a href="{{ route('admin.tasks.show', $child->id) }}"
                                                       class="text-decoration-none">
                                                        {{ $child->name }}
                                                    </a>
                                                </div>
                                                <div class="col-auto">
                                                    <small class="text-muted">
                                                        {{ $child->progress_percentage }}%
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Dependencies --}}
                    @if ($task->dependencies->count() > 0 || $task->dependents->count() > 0)
                        <div class="card mb-3">
                            <div class="card-header">
                                <h3 class="card-title">{{ trans('modules/task::task.dependency') }}</h3>
                            </div>
                            <div class="card-body">
                                @if ($task->dependencies->count() > 0)
                                    <h6>{{ trans('modules/task::task.dependency_before') }}</h6>
                                    <ul class="mb-3">
                                        @foreach ($task->dependencies as $dep)
                                            <li>
                                                <a href="{{ route('admin.tasks.show', $dep->successor_id) }}">
                                                    {{ $dep->successor?->name }}
                                                </a>
                                                ({{ __("modules/task::dependency.{$dep->dependency_type}") }})
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif

                                @if ($task->dependents->count() > 0)
                                    <h6>{{ trans('modules/task::task.dependent_tasks') }}</h6>
                                    <ul>
                                        @foreach ($task->dependents as $dep)
                                            <li>
                                                <a href="{{ route('admin.tasks.show', $dep->predecessor_id) }}">
                                                    {{ $dep->predecessor?->name }}
                                                </a>
                                                ({{ __("modules/task::dependency.{$dep->dependency_type}") }})
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Notes --}}
                    @if ($task->note)
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">{{ trans('modules/task::task.note') }}</h3>
                            </div>
                            <div class="card-body">
                                <p class="text-muted">{{ $task->note }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-ui.layouts::app>
