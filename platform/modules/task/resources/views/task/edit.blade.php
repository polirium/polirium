<x-ui.layouts::app>
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">{{ __('modules/task::task.edit') }}</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="row">
                <div class="col-md-8 mx-auto">
                    <form method="POST" action="{{ route('admin.tasks.update', $task->id) }}">
                        @csrf
                        @method('put')

                        <div class="card">
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">{{ trans('modules/task::task.task_name') }} <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="name" required
                                           value="{{ old('name', $task->name) }}">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">{{ trans('modules/task::task.project') }}</label>
                                    <select class="form-select" name="project_id">
                                        @foreach (\Polirium\Modules\Task\Models\Project::orderBy('name')->get() as $project)
                                            <option value="{{ $project->id }}"
                                                    {{ old('project_id', $task->project_id) == $project->id ? 'selected' : '' }}>
                                                {{ $project->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">{{ trans('modules/task::task.parent_task') }}</label>
                                    <select class="form-select" name="parent_id">
                                        <option value="">{{ trans('modules/task::task.none') }}</option>
                                        @if (old('project_id', $task->project_id))
                                            @php
                                                $projectId = old('project_id', $task->project_id);
                                                $rootTasks = \Polirium\Modules\Task\Models\Task::where('project_id', $projectId)->where('id', '!=', $task->id)->whereNull('parent_id')->orderBy('name')->get();
                                            @endphp
                                            @foreach ($rootTasks as $rootTask)
                                                <option value="{{ $rootTask->id }}"
                                                        {{ old('parent_id', $task->parent_id) == $rootTask->id ? 'selected' : '' }}>
                                                    {{ $rootTask->name }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">{{ trans('modules/task::task.description') }}</label>
                                    <textarea class="form-control" name="description" rows="4">{{ old('description', $task->description) }}</textarea>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">{{ trans('modules/task::task.status') }}</label>
                                        <select class="form-select" name="status">
                                            @foreach (['backlog', 'todo', 'in_progress', 'review', 'done', 'cancelled'] as $status)
                                                <option value="{{ $status }}"
                                                        {{ old('status', $task->status) === $status ? 'selected' : '' }}>
                                                    {{ __("modules/task::status.{$status}") }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">{{ trans('modules/task::task.priority') }}</label>
                                        <select class="form-select" name="priority">
                                            @foreach (['low', 'medium', 'high', 'urgent'] as $priority)
                                                <option value="{{ $priority }}"
                                                        {{ old('priority', $task->priority) === $priority ? 'selected' : '' }}>
                                                    {{ __("modules/task::priority.{$priority}") }}
                                                </option>
                                            @endforeach
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
                                                        {{ old('assigned_to', $task->assigned_to) == $user->id ? 'selected' : '' }}>
                                                    {{ $user->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">{{ trans('modules/task::task.sort_order') }}</label>
                                        <input type="number" class="form-control" name="sort_order"
                                               value="{{ old('sort_order', $task->sort_order) }}" min="0">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">{{ trans('modules/task::task.start_date') }}</label>
                                        <input type="date" class="form-control" name="planned_start_date"
                                               value="{{ old('planned_start_date', $task->planned_start_date?->format('Y-m-d')) }}">
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">{{ trans('modules/task::task.end_date') }}</label>
                                        <input type="date" class="form-control" name="planned_end_date"
                                               value="{{ old('planned_end_date', $task->planned_end_date?->format('Y-m-d')) }}">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">{{ trans('modules/task::task.actual_start_date') }}</label>
                                        <input type="date" class="form-control" name="actual_start_date"
                                               value="{{ old('actual_start_date', $task->actual_start_date?->format('Y-m-d')) }}">
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">{{ trans('modules/task::task.actual_end_date') }}</label>
                                        <input type="date" class="form-control" name="actual_end_date"
                                               value="{{ old('actual_end_date', $task->actual_end_date?->format('Y-m-d')) }}">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">{{ trans('modules/task::task.estimated_hours') }}</label>
                                        <input type="number" class="form-control" name="estimated_hours"
                                               value="{{ old('estimated_hours', $task->estimated_hours) }}" step="0.5" min="0">
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">{{ trans('modules/task::task.actual_hours') }}</label>
                                        <input type="number" class="form-control" name="actual_hours"
                                               value="{{ old('actual_hours', $task->actual_hours) }}" step="0.5" min="0">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">{{ trans('modules/task::task.progress_percent') }}</label>
                                    <input type="number" class="form-control" name="progress_percentage"
                                           min="0" max="100" step="1"
                                           value="{{ old('progress_percentage', $task->progress_percentage) }}">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">{{ trans('modules/task::task.note') }}</label>
                                    <textarea class="form-control" name="note" rows="2">{{ old('note', $task->note) }}</textarea>
                                </div>
                            </div>

                            <div class="card-footer text-end">
                                <a href="{{ route('admin.tasks.show', $task->id) }}" class="btn btn-outline-secondary">
                                    {{ __('core/base::general.cancel') }}
                                </a>
                                @can('tasks.edit')
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
