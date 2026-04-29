<x-ui.layouts::app>
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">{{ __('modules/task::project.edit') }}</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="row">
                <div class="col-md-8 mx-auto">
                    <form method="POST" action="{{ route('admin.projects.update', $project->id) }}">
                        @csrf
                        @method('put')

                        <div class="card">
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">{{ trans('modules/task::task.project_name') }} <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="name" required
                                           value="{{ old('name', $project->name) }}">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">{{ trans('modules/task::task.project_code') }}</label>
                                    <input type="text" class="form-control" name="code"
                                           value="{{ old('code', $project->code) }}">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">{{ trans('modules/task::task.description') }}</label>
                                    <textarea class="form-control" name="description" rows="4">{{ old('description', $project->description) }}</textarea>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">{{ trans('modules/task::task.status') }}</label>
                                        <select class="form-select" name="status">
                                            <option value="planning" {{ old('status', $project->status) === 'planning' ? 'selected' : '' }}>
                                                {{ __('modules/task::status.planning') }}
                                            </option>
                                            <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>
                                                {{ __('modules/task::status.active') }}
                                            </option>
                                            <option value="on_hold" {{ old('status') === 'on_hold' ? 'selected' : '' }}>
                                                {{ __('modules/task::status.on_hold') }}
                                            </option>
                                            <option value="completed" {{ old('status') === 'completed' ? 'selected' : '' }}>
                                                {{ __('modules/task::status.completed') }}
                                            </option>
                                            <option value="cancelled" {{ old('status') === 'cancelled' ? 'selected' : '' }}>
                                                {{ __('modules/task::status.cancelled') }}
                                            </option>
                                        </select>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">{{ trans('modules/task::task.priority') }}</label>
                                        <select class="form-select" name="priority">
                                            <option value="low" {{ old('priority', $project->priority) === 'low' ? 'selected' : '' }}>
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
                                        <label class="form-label">{{ trans('modules/task::task.start_date') }}</label>
                                        <input type="date" class="form-control" name="planned_start_date"
                                               value="{{ old('planned_start_date', $project->planned_start_date?->format('Y-m-d')) }}">
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">{{ trans('modules/task::task.end_date') }}</label>
                                        <input type="date" class="form-control" name="planned_end_date"
                                               value="{{ old('planned_end_date', $project->planned_end_date?->format('Y-m-d')) }}">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">{{ trans('modules/task::task.actual_start_date') }}</label>
                                        <input type="date" class="form-control" name="actual_start_date"
                                               value="{{ old('actual_start_date', $project->actual_start_date?->format('Y-m-d')) }}">
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">{{ trans('modules/task::task.actual_end_date') }}</label>
                                        <input type="date" class="form-control" name="actual_end_date"
                                               value="{{ old('actual_end_date', $project->actual_end_date?->format('Y-m-d')) }}">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">{{ trans('modules/task::task.budget') }}</label>
                                        <input type="number" class="form-control" name="budget" step="0.01"
                                               value="{{ old('budget', $project->budget) }}">
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">{{ trans('modules/task::task.progress_percent') }}</label>
                                        <input type="number" class="form-control" name="progress_percentage"
                                               min="0" max="100" step="0.01"
                                               value="{{ old('progress_percentage', $project->progress_percentage) }}">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">{{ trans('modules/task::task.note') }}</label>
                                    <textarea class="form-control" name="note" rows="2">{{ old('note', $project->note) }}</textarea>
                                </div>
                            </div>

                            <div class="card-footer text-end">
                                <a href="{{ route('admin.projects.show', $project->id) }}" class="btn btn-outline-secondary">
                                    {{ __('core/base::general.cancel') }}
                                </a>
                                @can('projects.edit')
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
