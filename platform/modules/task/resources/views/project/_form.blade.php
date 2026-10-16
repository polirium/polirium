@php
    $statuses = $project->exists ? \Polirium\Modules\Task\Models\Project::STATUSES : ['planning', 'active', 'on_hold'];
    $date = fn ($field) => old($field, $project->{$field}?->format('Y-m-d'));
@endphp

<div class="card">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label required" for="project-name">{{ trans('modules/task::task.project_name') }}</label>
                <input type="text" id="project-name" name="name" maxlength="255" required autofocus
                       class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $project->name) }}">
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label" for="project-code">{{ trans('modules/task::task.project_code') }}</label>
                <input type="text" id="project-code" name="code" maxlength="50"
                       class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $project->code) }}"
                       placeholder="{{ trans('modules/task::task.auto_generate_if_empty') }}">
                @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-12">
                <label class="form-label" for="project-description">{{ trans('modules/task::task.description') }}</label>
                <textarea id="project-description" name="description" rows="4"
                          class="form-control @error('description') is-invalid @enderror">{{ old('description', $project->description) }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label class="form-label" for="project-status">{{ trans('modules/task::task.status') }}</label>
                <select id="project-status" name="status" class="form-select @error('status') is-invalid @enderror">
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}" @selected(old('status', $project->status) === $status)>{{ __('modules/task::status.' . $status) }}</option>
                    @endforeach
                </select>
                @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="project-priority">{{ trans('modules/task::task.priority') }}</label>
                <select id="project-priority" name="priority" class="form-select @error('priority') is-invalid @enderror">
                    @foreach (['low', 'medium', 'high', 'urgent'] as $priority)
                        <option value="{{ $priority }}" @selected(old('priority', $project->priority) === $priority)>{{ __('modules/task::priority.' . $priority) }}</option>
                    @endforeach
                </select>
                @error('priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label class="form-label" for="project-start">{{ trans('modules/task::task.start_date') }}</label>
                <input type="date" id="project-start" name="planned_start_date"
                       class="form-control @error('planned_start_date') is-invalid @enderror" value="{{ $date('planned_start_date') }}">
                @error('planned_start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="project-end">{{ trans('modules/task::task.end_date') }}</label>
                <input type="date" id="project-end" name="planned_end_date"
                       class="form-control @error('planned_end_date') is-invalid @enderror" value="{{ $date('planned_end_date') }}">
                @error('planned_end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label class="form-label" for="project-budget">{{ trans('modules/task::task.budget') }}</label>
                <div class="input-group has-validation">
                    <input type="number" id="project-budget" name="budget" min="0" step="1000"
                           class="form-control @error('budget') is-invalid @enderror" value="{{ old('budget', (float) $project->budget) }}">
                    <span class="input-group-text">VNĐ</span>
                    @error('budget')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="project-note">{{ trans('modules/task::task.note') }}</label>
                <input type="text" id="project-note" name="note" maxlength="255"
                       class="form-control @error('note') is-invalid @enderror" value="{{ old('note', $project->note) }}">
                @error('note')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
    <div class="card-footer d-flex justify-content-end gap-2">
        <a href="{{ $project->exists ? route('admin.projects.show', $project->id) : route('admin.projects.index') }}" class="btn btn-outline-secondary">
            {{ __('core/base::general.cancel') }}
        </a>
        <button type="submit" class="btn btn-primary">
            {!! tabler_icon('device-floppy', ['class' => 'icon']) !!} {{ __('core/base::general.save') }}
        </button>
    </div>
</div>
