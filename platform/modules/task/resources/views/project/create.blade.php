<x-ui.layouts::app>
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">{{ __('modules/task::project.create') }}</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="row">
                <div class="col-md-8 mx-auto">
                    <form method="POST" action="{{ route('admin.projects.store') }}">
                        @csrf

                        <div class="card">
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">{{ trans('modules/task::task.project_name') }} <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="name" required
                                           value="{{ old('name') }}">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">{{ trans('modules/task::task.project_code') }}</label>
                                    <input type="text" class="form-control" name="code"
                                           placeholder="{{ trans('modules/task::task.auto_generate_if_empty') }}"
                                           value="{{ old('code') }}">
                                    <small class="form-hint">{{ trans('modules/task::task.auto_generate_if_empty') }}</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">{{ trans('modules/task::task.description') }}</label>
                                    <textarea class="form-control" name="description" rows="4">{{ old('description') }}</textarea>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">{{ trans('modules/task::task.status') }}</label>
                                        <select class="form-select" name="status">
                                            <option value="planning" {{ old('status', 'planning') === 'planning' ? 'selected' : '' }}>
                                                {{ __('modules/task::status.planning') }}
                                            </option>
                                            <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>
                                                {{ __('modules/task::status.active') }}
                                            </option>
                                            <option value="on_hold" {{ old('status') === 'on_hold' ? 'selected' : '' }}>
                                                {{ __('modules/task::status.on_hold') }}
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

                                <div class="mb-3">
                                    <label class="form-label">{{ trans('modules/task::task.budget') }}</label>
                                    <input type="number" class="form-control" name="budget" step="0.01"
                                           value="{{ old('budget', 0) }}">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">{{ trans('modules/task::task.note') }}</label>
                                    <textarea class="form-control" name="note" rows="2">{{ old('note') }}</textarea>
                                </div>
                            </div>

                            <div class="card-footer text-end">
                                <a href="{{ route('admin.projects.index') }}" class="btn btn-outline-secondary">
                                    {{ __('core/base::general.cancel') }}
                                </a>
                                @can('projects.create')
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
