<x-ui.layouts::app>
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">{{ __('modules/task::task.create') }}</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="row">
                <div class="col-md-8 mx-auto">
                    <form method="POST" action="{{ route('admin.tasks.store') }}">
                        @csrf

                        <div class="card">
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">{{ trans('modules/task::task.task_name') }} <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="name" required
                                           value="{{ old('name') }}">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">{{ trans('modules/task::task.project') }}</label>
                                    <select class="form-select" name="project_id">
                                        <option value="">{{ __('core/base::general.select') }}</option>
                                        @foreach (\Polirium\Modules\Task\Models\Project::active()->orderBy('name')->get() as $project)
                                            <option value="{{ $project->id }}"
                                                    {{ old('project_id', request('project_id')) == $project->id ? 'selected' : '' }}>
                                                {{ $project->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">{{ trans('modules/task::task.parent_task') }}</label>
                                    <select class="form-select" name="parent_id">
                                        <option value="">{{ trans('modules/task::task.none') }}</option>
                                        @if (request('project_id') || old('project_id'))
                                            @php
                                                $projectId = request('project_id') ?: old('project_id');
                                                $rootTasks = \Polirium\Modules\Task\Models\Task::where('project_id', $projectId)->whereNull('parent_id')->orderBy('name')->get();
                                            @endphp
                                            @foreach ($rootTasks as $task)
                                                <option value="{{ $task->id }}"
                                                        {{ old('parent_id') == $task->id ? 'selected' : '' }}>
                                                    {{ $task->name }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                    <small class="form-hint">{{ trans('modules/task::task.only_root_as_parent') }}</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">{{ trans('modules/task::task.description') }}</label>
                                    <textarea class="form-control" name="description" rows="4">{{ old('description') }}</textarea>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">{{ trans('modules/task::task.status') }}</label>
                                        <select class="form-select" name="status">
                                            <option value="backlog" {{ old('status', 'backlog') === 'backlog' ? 'selected' : '' }}>
                                                {{ __('modules/task::status.backlog') }}
                                            </option>
                                            <option value="todo" {{ old('status') === 'todo' ? 'selected' : '' }}>
                                                {{ __('modules/task::status.todo') }}
                                            </option>
                                            <option value="in_progress" {{ old('status') === 'in_progress' ? 'selected' : '' }}>
                                                {{ __('modules/task::status.in_progress') }}
                                            </option>
                                            <option value="review" {{ old('status') === 'review' ? 'selected' : '' }}>
                                                {{ __('modules/task::status.review') }}
                                            </option>
                                            <option value="done" {{ old('status') === 'done' ? 'selected' : '' }}>
                                                {{ __('modules/task::status.done') }}
                                            </option>
                                        </select>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">{{ trans('modules/task::task.priority') }}</label>
                                        <select class="form-select" name="priority">
                                            <option value="low" {{ old('priority', 'medium') === 'low' ? 'selected' : '' }}>
                                                {{ __('modules/task::priority.low') }}
                                            </option>
                                            <option value="medium" {{ old('priority') === 'medium' ? 'selected' : '' }}>
                                                {{ __('modules/task::priority.medium') }}
                                            </option>
                                            <option value="high" {{ old('priority') === 'high' ? 'selected' : '' }}>
                                                {{ __('modules/task::priority.high') }}
                                            </option>
                                            <option value="urgent" {{ old('priority') === 'urgent' ? 'selected' : '' }}>
                                                {{ __('modules/task::priority.urgent') }}
                                            </option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">{{ trans('modules/task::task.assignee') }}</label>
                                        <select class="form-select" name="assigned_to">
                                            <option value="">{{ trans('modules/task::task.unassigned') }}</option>
                                            @foreach ($users ?? [] as $user)
                                                <option value="{{ $user->id }}"
                                                        {{ old('assigned_to') == $user->id ? 'selected' : '' }}>
                                                    {{ $user->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">{{ trans('modules/task::task.sort_order') }}</label>
                                        <input type="number" class="form-control" name="sort_order"
                                               value="{{ old('sort_order', 0) }}" min="0">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">{{ trans('modules/task::task.start_date') }}</label>
                                        <input type="date" class="form-control" name="planned_start_date"
                                               value="{{ old('planned_start_date') }}">
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">{{ trans('modules/task::task.end_date') }}</label>
                                        <input type="date" class="form-control" name="planned_end_date"
                                               value="{{ old('planned_end_date') }}">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">{{ trans('modules/task::task.estimated_hours') }}</label>
                                        <input type="number" class="form-control" name="estimated_hours"
                                               value="{{ old('estimated_hours', 0) }}" step="0.5" min="0">
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">{{ trans('modules/task::task.actual_hours') }}</label>
                                        <input type="number" class="form-control" name="actual_hours"
                                               value="{{ old('actual_hours', 0) }}" step="0.5" min="0">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">{{ trans('modules/task::task.progress_percent') }}</label>
                                    <input type="number" class="form-control" name="progress_percentage"
                                           min="0" max="100" step="1" value="{{ old('progress_percentage', 0) }}">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">{{ trans('modules/task::task.note') }}</label>
                                    <textarea class="form-control" name="note" rows="2">{{ old('note') }}</textarea>
                                </div>
                            </div>

                            <div class="card-footer text-end">
                                <a href="{{ route('admin.tasks.index') }}" class="btn btn-outline-secondary">
                                    {{ __('core/base::general.cancel') }}
                                </a>
                                @can('tasks.create')
                                    <button type="submit" class="btn btn-primary">
                                        {!! tabler_icon('device-floppy', ['class' => 'icon']) !!}
                                        {{ __('core/base::general.save') }}
                                    </button>
                                @endcan
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-ui.layouts::app>
