@php
    use Polirium\Modules\Task\Models\Task;

    $date = fn ($field) => old($field, $task->{$field}?->format('Y-m-d'));
    $number = fn ($field) => old($field, $task->exists ? (float) $task->{$field} : 0);
    $hasTimeLogs = $task->exists && $task->timeLogs()->exists();
    $selectedProject = (string) old('project_id', $task->project_id);
@endphp

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label required" for="task-name">{{ trans('modules/task::task.task_name') }}</label>
                    <input type="text" id="task-name" name="name" maxlength="255" required autofocus
                           class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $task->name) }}">
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label" for="task-project">{{ trans('modules/task::task.project') }}</label>
                        <select id="task-project" name="project_id" class="form-select @error('project_id') is-invalid @enderror">
                            <option value="">{{ __('modules/task::task.select_project') }}</option>
                            @foreach ($projects as $project)
                                <option value="{{ $project->id }}" @selected($selectedProject === (string) $project->id)>{{ $project->code }} · {{ $project->name }}</option>
                            @endforeach
                        </select>
                        @error('project_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="task-parent">{{ trans('modules/task::task.parent_task') }}</label>
                        <select id="task-parent" name="parent_id" class="form-select @error('parent_id') is-invalid @enderror">
                            <option value="">{{ __('modules/task::task.select_parent') }}</option>
                            @foreach ($parents as $parent)
                                <option value="{{ $parent->id }}" data-project="{{ $parent->project_id }}"
                                        @selected((string) old('parent_id', $task->parent_id) === (string) $parent->id)>{{ $parent->code }} · {{ $parent->name }}</option>
                            @endforeach
                        </select>
                        @error('parent_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @else
                            <small class="form-hint">{{ __('modules/task::task.parent_hint') }}</small>
                        @enderror
                    </div>
                </div>

                <div>
                    <label class="form-label" for="task-description">{{ trans('modules/task::task.description') }}</label>
                    <textarea id="task-description" name="description" rows="6"
                              class="form-control @error('description') is-invalid @enderror">{{ old('description', $task->description) }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label" for="task-status">{{ trans('modules/task::task.status') }}</label>
                    <select id="task-status" name="status" class="form-select @error('status') is-invalid @enderror">
                        @foreach (Task::STATUSES as $status)
                            <option value="{{ $status }}" @selected(old('status', $task->status) === $status)>{{ __('modules/task::status.' . $status) }}</option>
                        @endforeach
                    </select>
                    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label" for="task-priority">{{ trans('modules/task::task.priority') }}</label>
                    <select id="task-priority" name="priority" class="form-select @error('priority') is-invalid @enderror">
                        @foreach (Task::PRIORITIES as $priority)
                            <option value="{{ $priority }}" @selected(old('priority', $task->priority) === $priority)>{{ __('modules/task::priority.' . $priority) }}</option>
                        @endforeach
                    </select>
                    @error('priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label" for="task-assignee">{{ trans('modules/task::task.assignee') }}</label>
                    <select id="task-assignee" name="assigned_to" class="form-select @error('assigned_to') is-invalid @enderror">
                        <option value="">{{ trans('modules/task::task.unassigned') }}</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected((string) old('assigned_to', $task->assigned_to) === (string) $user->id)>{{ $user->name ?: $user->username }}</option>
                        @endforeach
                    </select>
                    @error('assigned_to')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label" for="task-start">{{ trans('modules/task::task.start_date') }}</label>
                        <input type="date" id="task-start" name="planned_start_date"
                               class="form-control @error('planned_start_date') is-invalid @enderror" value="{{ $date('planned_start_date') }}">
                        @error('planned_start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-6">
                        <label class="form-label" for="task-end">{{ trans('modules/task::task.end_date') }}</label>
                        <input type="date" id="task-end" name="planned_end_date"
                               class="form-control @error('planned_end_date') is-invalid @enderror" value="{{ $date('planned_end_date') }}">
                        @error('planned_end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                @if ($task->exists)
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label" for="task-actual-start">{{ trans('modules/task::task.actual_start') }}</label>
                            <input type="date" id="task-actual-start" name="actual_start_date"
                                   class="form-control @error('actual_start_date') is-invalid @enderror" value="{{ $date('actual_start_date') }}">
                            @error('actual_start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="task-actual-end">{{ trans('modules/task::task.actual_end') }}</label>
                            <input type="date" id="task-actual-end" name="actual_end_date"
                                   class="form-control @error('actual_end_date') is-invalid @enderror" value="{{ $date('actual_end_date') }}">
                            @error('actual_end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                @endif

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label" for="task-estimated">{{ trans('modules/task::task.estimated_hours') }}</label>
                        <input type="number" id="task-estimated" name="estimated_hours" min="0" step="0.25"
                               class="form-control @error('estimated_hours') is-invalid @enderror" value="{{ $number('estimated_hours') }}">
                        @error('estimated_hours')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-6">
                        <label class="form-label" for="task-actual">{{ trans('modules/task::task.actual_hours') }}</label>
                        <input type="number" id="task-actual" name="actual_hours" min="0" step="0.25" @disabled($hasTimeLogs)
                               class="form-control @error('actual_hours') is-invalid @enderror" value="{{ $number('actual_hours') }}">
                        @error('actual_hours')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @else
                            @if ($hasTimeLogs)<small class="form-hint">{{ __('modules/task::task.actual_hours_from_logs') }}</small>@endif
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label d-flex justify-content-between" for="task-progress">
                        <span>{{ trans('modules/task::task.progress_percent') }}</span>
                        <span class="text-secondary" id="task-progress-value">{{ (int) $number('progress_percentage') }}%</span>
                    </label>
                    <input type="range" id="task-progress" name="progress_percentage" min="0" max="100" step="5"
                           class="form-range @error('progress_percentage') is-invalid @enderror" value="{{ (int) $number('progress_percentage') }}"
                           oninput="document.getElementById('task-progress-value').textContent = this.value + '%'">
                    @error('progress_percentage')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="row g-2">
                    <div class="col-8">
                        <label class="form-label" for="task-note">{{ trans('modules/task::task.note') }}</label>
                        <input type="text" id="task-note" name="note" maxlength="255"
                               class="form-control @error('note') is-invalid @enderror" value="{{ old('note', $task->note) }}">
                        @error('note')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-4">
                        <label class="form-label" for="task-sort">{{ trans('modules/task::task.sort_order') }}</label>
                        <input type="number" id="task-sort" name="sort_order" min="0" step="1"
                               class="form-control @error('sort_order') is-invalid @enderror" value="{{ old('sort_order', (int) $task->sort_order) }}">
                        @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-end gap-2">
                <a href="{{ $task->exists ? route('admin.tasks.show', $task->id) : url()->previous(route('admin.tasks.index')) }}" class="btn btn-outline-secondary">
                    {{ __('core/base::general.cancel') }}
                </a>
                <button type="submit" class="btn btn-primary">
                    {!! tabler_icon('device-floppy', ['class' => 'icon']) !!} {{ __('core/base::general.save') }}
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        (function () {
            const project = document.getElementById('task-project');
            const parent = document.getElementById('task-parent');

            if (!project || !parent) return;

            // A parent must belong to the same project: only list those.
            const filterParents = () => {
                [...parent.options].forEach((option) => {
                    if (!option.value) return;
                    const match = (option.dataset.project || '') === project.value;
                    option.hidden = !match;
                    option.disabled = !match;
                });

                if (parent.selectedOptions[0]?.disabled) parent.value = '';
            };

            project.addEventListener('change', filterParents);
            filterParents();
        })();
    </script>
@endpush
