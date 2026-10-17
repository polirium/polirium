@php
    use Polirium\Modules\Task\Models\Task;

    $tab = in_array(request('tab'), ['overview', 'comments', 'time', 'files', 'dependencies'], true) ? request('tab') : 'overview';
    $progress = $task->status === 'done' ? 100 : $task->progress;
    $daysLeft = $task->planned_end_date ? (int) now()->startOfDay()->diffInDays($task->planned_end_date, false) : null;
    $canEdit = auth()->user()->can('tasks.edit');
@endphp

<x-ui.layouts::app>
    <x-slot:title>{{ $task->name }}</x-slot:title>
    <x-slot:subtitle>
        <code>{{ $task->code }}</code>
        <span class="badge bg-{{ $task->status_color }}-lt ms-1">{{ $task->status_label }}</span>
        <span class="badge bg-{{ $task->priority_color }}-lt ms-1">{{ $task->priority_label }}</span>
        @if ($task->project)
            <span class="ms-1">· <a href="{{ route('admin.projects.show', $task->project_id) }}">{{ $task->project->name }}</a></span>
        @endif
    </x-slot:subtitle>
    <x-slot:actions>
        <x-ui::button color="secondary" outline icon="arrow-left" :href="$task->project_id ? route('admin.projects.show', $task->project_id) : route('admin.tasks.index')">
            {{ __('core/base::general.back') }}
        </x-ui::button>
        @can('tasks.destroy')
            <form method="POST" action="{{ route('admin.tasks.destroy', $task->id) }}" class="d-inline"
                  onsubmit="return confirm(@js(__('modules/task::task.confirm_delete_task')))">
                @csrf
                @method('DELETE')
                <x-ui::button color="danger" outline icon="trash" type="submit">{{ __('core/base::general.delete') }}</x-ui::button>
            </form>
        @endcan
        @can('tasks.create')
            <x-ui::button color="secondary" outline icon="subtask" :href="route('admin.tasks.create', ['project_id' => $task->project_id, 'parent_id' => $task->id])">
                {{ __('modules/task::task.add_subtask') }}
            </x-ui::button>
        @endcan
        @can('tasks.edit')
            <x-ui::button color="primary" icon="pencil" :href="route('admin.tasks.edit', $task->id)">
                {{ __('core/base::general.edit') }}
            </x-ui::button>
        @endcan
    </x-slot:actions>

    @include('modules/task::partials.alerts')

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card" id="task-tabs">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" role="tablist">
                        @foreach ([
                            'overview' => [__('modules/task::task.overview'), null],
                            'comments' => [__('modules/task::task.comments'), $task->comments->count()],
                            'time' => [__('modules/task::task.time_logs'), $task->timeLogs->count()],
                            'files' => [__('modules/task::task.attachments'), $task->attachments->count()],
                            'dependencies' => [__('modules/task::task.dependency'), $task->dependents->count() + $task->dependencies->count()],
                        ] as $key => [$label, $count])
                            <li class="nav-item" role="presentation">
                                <a href="#tab-{{ $key }}" class="nav-link {{ $tab === $key ? 'active' : '' }}" data-bs-toggle="tab" role="tab">
                                    {{ $label }}
                                    @if ($count)<span class="badge bg-secondary-lt ms-1">{{ $count }}</span>@endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="tab-content">
                    {{-- Overview --}}
                    <div class="tab-pane card-body {{ $tab === 'overview' ? 'active show' : '' }}" id="tab-overview" role="tabpanel">
                        <h4 class="subheader">{{ __('modules/task::task.description') }}</h4>
                        @if ($task->description)
                            <div class="text-break mb-4">{!! nl2br(e($task->description)) !!}</div>
                        @else
                            <p class="text-secondary mb-4">{{ __('modules/task::task.no_description') }}</p>
                        @endif

                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h4 class="subheader mb-0">{{ __('modules/task::task.child_tasks') }}</h4>
                            @can('tasks.create')
                                <a href="{{ route('admin.tasks.create', ['project_id' => $task->project_id, 'parent_id' => $task->id]) }}" class="btn btn-sm btn-primary">
                                    {!! tabler_icon('plus', ['class' => 'icon']) !!} {{ __('modules/task::task.add_subtask') }}
                                </a>
                            @endcan
                        </div>
                        @forelse ($task->children as $child)
                            <a href="{{ route('admin.tasks.show', $child->id) }}" class="d-flex align-items-center gap-2 py-2 border-bottom text-reset text-decoration-none">
                                <span class="badge bg-{{ $child->status_color }}-lt">{{ $child->status_label }}</span>
                                <span class="flex-fill">{{ $child->name }}</span>
                                <span class="text-secondary small">{{ $child->assignedTo?->name }}</span>
                                <span class="text-secondary small">{{ $child->status === 'done' ? 100 : $child->progress }}%</span>
                            </a>
                        @empty
                            <p class="text-secondary mb-0">{{ __('modules/task::task.no_tasks') }}</p>
                        @endforelse

                        @if ($task->note)
                            <h4 class="subheader mt-4">{{ __('modules/task::task.note') }}</h4>
                            <p class="text-secondary mb-0">{{ $task->note }}</p>
                        @endif
                    </div>

                    {{-- Comments --}}
                    <div class="tab-pane card-body {{ $tab === 'comments' ? 'active show' : '' }}" id="tab-comments" role="tabpanel">
                        <form method="POST" action="{{ route('admin.tasks.comments.store', $task->id) }}" class="mb-4">
                            @csrf
                            <textarea name="content" rows="3" maxlength="5000" required class="form-control @error('content') is-invalid @enderror"
                                      placeholder="{{ __('modules/task::task.write_comment') }}">{{ old('content') }}</textarea>
                            @error('content')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="text-end mt-2">
                                <button type="submit" class="btn btn-primary">{!! tabler_icon('send', ['class' => 'icon']) !!} {{ __('modules/task::task.add_comment') }}</button>
                            </div>
                        </form>

                        @forelse ($task->comments as $comment)
                            <div class="d-flex gap-3 mb-3">
                                <span class="avatar avatar-sm rounded-circle">{{ mb_strtoupper(mb_substr($comment->user?->name ?? '?', 0, 1)) }}</span>
                                <div class="flex-fill">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <strong>{{ $comment->user?->name ?? '—' }}</strong>
                                            <span class="text-secondary small ms-1" title="{{ $comment->created_at->format('d/m/Y H:i') }}">{{ $comment->created_at->diffForHumans() }}</span>
                                        </div>
                                        @if ($comment->user_id === auth()->id() || $canEdit)
                                            <form method="POST" action="{{ route('admin.tasks.comments.destroy', [$task->id, $comment->id]) }}"
                                                  onsubmit="return confirm(@js(__('core/base::general.confirm_delete')))">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-icon btn-ghost-danger" title="{{ __('core/base::general.delete') }}" aria-label="{{ __('core/base::general.delete') }}">{!! tabler_icon('trash', ['class' => 'icon']) !!}</button>
                                            </form>
                                        @endif
                                    </div>
                                    <div class="text-break">{!! nl2br(e($comment->content)) !!}</div>
                                </div>
                            </div>
                        @empty
                            <p class="text-secondary mb-0">{{ __('modules/task::task.no_comments') }}</p>
                        @endforelse
                    </div>

                    {{-- Time logs --}}
                    <div class="tab-pane card-body {{ $tab === 'time' ? 'active show' : '' }}" id="tab-time" role="tabpanel">
                        <form method="POST" action="{{ route('admin.tasks.time-logs.store', $task->id) }}" class="row g-2 align-items-end mb-4">
                            @csrf
                            <div class="col-sm-3">
                                <label class="form-label">{{ __('modules/task::task.log_date') }}</label>
                                <input type="date" name="log_date" max="{{ now()->toDateString() }}" required
                                       class="form-control @error('log_date') is-invalid @enderror" value="{{ old('log_date', now()->toDateString()) }}">
                                @error('log_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-sm-2">
                                <label class="form-label">{{ __('modules/task::task.hours') }}</label>
                                <input type="number" name="hours" min="0.25" max="24" step="0.25" required
                                       class="form-control @error('hours') is-invalid @enderror" value="{{ old('hours', 1) }}">
                                @error('hours')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-sm-5">
                                <label class="form-label">{{ __('modules/task::task.work_description') }}</label>
                                <input type="text" name="description" maxlength="1000"
                                       class="form-control @error('description') is-invalid @enderror" value="{{ old('description') }}">
                                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-sm-2 d-grid">
                                <button type="submit" class="btn btn-primary">{!! tabler_icon('clock', ['class' => 'icon']) !!} {{ __('modules/task::task.log_time') }}</button>
                            </div>
                        </form>

                        @if ($task->timeLogs->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table table-vcenter">
                                    <thead>
                                        <tr>
                                            <th>{{ __('modules/task::task.log_date') }}</th>
                                            <th>{{ __('modules/task::task.assignee') }}</th>
                                            <th>{{ __('modules/task::task.work_description') }}</th>
                                            <th class="text-end">{{ __('modules/task::task.hours') }}</th>
                                            <th class="w-1"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($task->timeLogs as $log)
                                            <tr>
                                                <td>{{ $log->log_date->format('d/m/Y') }}</td>
                                                <td>{{ $log->user?->name ?? '—' }}</td>
                                                <td class="text-secondary">{{ $log->description }}</td>
                                                <td class="text-end">{{ Task::formatHours($log->hours) }}</td>
                                                <td>
                                                    @if ($log->user_id === auth()->id() || $canEdit)
                                                        <form method="POST" action="{{ route('admin.tasks.time-logs.destroy', [$task->id, $log->id]) }}"
                                                              onsubmit="return confirm(@js(__('core/base::general.confirm_delete')))">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-icon btn-ghost-danger" title="{{ __('core/base::general.delete') }}" aria-label="{{ __('core/base::general.delete') }}">{!! tabler_icon('trash', ['class' => 'icon']) !!}</button>
                                                        </form>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="3" class="text-end">{{ __('modules/task::task.actual_hours') }}</th>
                                            <th class="text-end">{{ Task::formatHours($task->timeLogs->sum('hours')) }}</th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        @else
                            <p class="text-secondary mb-0">{{ __('modules/task::task.no_time_logs') }}</p>
                        @endif
                    </div>

                    {{-- Attachments --}}
                    <div class="tab-pane card-body {{ $tab === 'files' ? 'active show' : '' }}" id="tab-files" role="tabpanel">
                        <form method="POST" action="{{ route('admin.tasks.attachments.store', $task->id) }}" enctype="multipart/form-data" class="mb-4">
                            @csrf
                            <div class="input-group has-validation">
                                <input type="file" name="file" required class="form-control @error('file') is-invalid @enderror">
                                <button type="submit" class="btn btn-primary">{!! tabler_icon('upload', ['class' => 'icon']) !!} {{ __('modules/task::task.upload') }}</button>
                                @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <small class="form-hint">{{ __('modules/task::task.upload_hint') }}</small>
                        </form>

                        @forelse ($task->attachments as $file)
                            <div class="d-flex align-items-center gap-3 py-2 border-bottom">
                                <span class="text-secondary">{!! tabler_icon('file', ['class' => 'icon']) !!}</span>
                                <div class="flex-fill text-truncate">
                                    <a href="{{ route('admin.tasks.attachments.download', [$task->id, $file->id]) }}">{{ $file->file_name }}</a>
                                    <div class="text-secondary small">{{ $file->file_size_human }} · {{ $file->uploadedBy?->name }} · {{ $file->created_at->format('d/m/Y H:i') }}</div>
                                </div>
                                <a href="{{ route('admin.tasks.attachments.download', [$task->id, $file->id]) }}" class="btn btn-sm btn-icon btn-ghost-secondary" title="{{ __('modules/task::task.download') }}" aria-label="{{ __('modules/task::task.download') }}">{!! tabler_icon('download', ['class' => 'icon']) !!}</a>
                                @if ($file->uploaded_by === auth()->id() || $canEdit)
                                    <form method="POST" action="{{ route('admin.tasks.attachments.destroy', [$task->id, $file->id]) }}"
                                          onsubmit="return confirm(@js(__('core/base::general.confirm_delete')))">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-icon btn-ghost-danger" title="{{ __('core/base::general.delete') }}" aria-label="{{ __('core/base::general.delete') }}">{!! tabler_icon('trash', ['class' => 'icon']) !!}</button>
                                    </form>
                                @endif
                            </div>
                        @empty
                            <p class="text-secondary mb-0">{{ __('modules/task::task.no_attachments') }}</p>
                        @endforelse
                    </div>

                    {{-- Dependencies --}}
                    <div class="tab-pane card-body {{ $tab === 'dependencies' ? 'active show' : '' }}" id="tab-dependencies" role="tabpanel">
                        @if ($canEdit)
                            <form method="POST" action="{{ route('admin.tasks.dependencies.store', $task->id) }}" class="row g-2 align-items-end mb-4">
                                @csrf
                                <div class="col-sm-5">
                                    <label class="form-label">{{ __('modules/task::task.predecessor') }}</label>
                                    <select name="predecessor_id" required class="form-select @error('predecessor_id') is-invalid @enderror">
                                        <option value="">{{ __('modules/task::task.select_task') }}</option>
                                        @foreach ($dependencyCandidates as $candidate)
                                            <option value="{{ $candidate->id }}" @selected((string) old('predecessor_id') === (string) $candidate->id)>{{ $candidate->code }} · {{ $candidate->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('predecessor_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-sm-4">
                                    <label class="form-label">{{ __('modules/task::task.dependency_type') }}</label>
                                    <select name="dependency_type" class="form-select">
                                        @foreach (['finish_to_start', 'start_to_start', 'finish_to_finish', 'start_to_finish'] as $type)
                                            <option value="{{ $type }}" @selected(old('dependency_type', 'finish_to_start') === $type)>{{ __('modules/task::dependency.' . $type) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-sm-1">
                                    <label class="form-label text-nowrap">{{ __('modules/task::task.lag_days') }}</label>
                                    <input type="number" name="lag_days" min="-365" max="365" class="form-control" value="{{ old('lag_days', 0) }}">
                                </div>
                                <div class="col-sm-2 d-grid">
                                    <button type="submit" class="btn btn-primary">{!! tabler_icon('link', ['class' => 'icon']) !!} {{ __('modules/task::task.add_dependency') }}</button>
                                </div>
                            </form>
                        @endif

                        @foreach ([
                            [__('modules/task::task.waits_for'), $task->dependents, 'predecessor'],
                            [__('modules/task::task.blocks'), $task->dependencies, 'successor'],
                        ] as [$heading, $items, $relation])
                            <h4 class="subheader">{{ $heading }}</h4>
                            @forelse ($items as $dependency)
                                @php($other = $dependency->{$relation})
                                <div class="d-flex align-items-center gap-2 py-2 border-bottom">
                                    @if ($other)
                                        <span class="badge bg-{{ $other->status_color }}-lt">{{ $other->status_label }}</span>
                                        <a href="{{ route('admin.tasks.show', $other->id) }}" class="flex-fill">{{ $other->code }} · {{ $other->name }}</a>
                                    @else
                                        <span class="flex-fill text-secondary">—</span>
                                    @endif
                                    <span class="badge bg-secondary-lt" title="{{ $dependency->dependency_type_label }}">{{ $dependency->short_type_label }}</span>
                                    @if ($dependency->lag_days)<span class="text-secondary small">{{ $dependency->lag_days > 0 ? '+' : '' }}{{ $dependency->lag_days }}d</span>@endif
                                    @if ($canEdit)
                                        <form method="POST" action="{{ route('admin.tasks.dependencies.destroy', [$task->id, $dependency->id]) }}"
                                              onsubmit="return confirm(@js(__('core/base::general.confirm_delete')))">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-icon btn-ghost-danger" title="{{ __('core/base::general.delete') }}" aria-label="{{ __('core/base::general.delete') }}">{!! tabler_icon('x', ['class' => 'icon']) !!}</button>
                                        </form>
                                    @endif
                                </div>
                            @empty
                                <p class="text-secondary">{{ __('modules/task::task.no_dependencies') }}</p>
                            @endforelse
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            @can('tasks.edit')
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="subheader mb-2">{{ __('modules/task::task.change_status') }}</div>
                        <form method="POST" action="{{ route('admin.tasks.status', $task->id) }}" class="d-flex flex-wrap gap-1">
                            @csrf
                            @method('PATCH')
                            @foreach (Task::STATUSES as $status)
                                <button type="submit" name="status" value="{{ $status }}" @disabled($task->status === $status)
                                        class="btn btn-sm {{ $task->status === $status ? 'btn-' . Task::STATUS_COLORS[$status] : 'btn-outline-' . Task::STATUS_COLORS[$status] }}">
                                    {{ __('modules/task::status.' . $status) }}
                                </button>
                            @endforeach
                        </form>
                    </div>
                </div>
            @endcan

            <div class="card">
                <div class="card-body">
                    <div class="subheader">{{ __('modules/task::task.progress') }}</div>
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="progress progress-sm flex-fill">
                            <div class="progress-bar bg-{{ $progress >= 100 ? 'success' : 'primary' }}" style="width: {{ $progress }}%"></div>
                        </div>
                        <strong>{{ $progress }}%</strong>
                    </div>

                    <dl class="row mb-0">
                        <dt class="col-5">{{ __('modules/task::task.assignee') }}</dt>
                        <dd class="col-7">{{ $task->assignedTo?->name ?? __('modules/task::task.unassigned') }}</dd>

                        @if ($task->parent)
                            <dt class="col-5">{{ __('modules/task::task.parent_task') }}</dt>
                            <dd class="col-7"><a href="{{ route('admin.tasks.show', $task->parent_id) }}">{{ $task->parent->name }}</a></dd>
                        @endif

                        <dt class="col-5">{{ __('modules/task::task.start_date') }}</dt>
                        <dd class="col-7">{{ $task->planned_start_date?->format('d/m/Y') ?? '—' }}</dd>

                        <dt class="col-5">{{ __('modules/task::task.end_date') }}</dt>
                        <dd class="col-7">
                            {{ $task->planned_end_date?->format('d/m/Y') ?? '—' }}
                            @if ($daysLeft !== null && ! in_array($task->status, ['done', 'cancelled'], true))
                                <div class="small {{ $daysLeft < 0 ? 'text-danger' : ($daysLeft === 0 ? 'text-warning' : 'text-secondary') }}">
                                    {{ $daysLeft < 0 ? __('modules/task::task.overdue_days', ['days' => -$daysLeft]) : ($daysLeft === 0 ? __('modules/task::task.due_today') : __('modules/task::task.remaining', ['days' => $daysLeft])) }}
                                </div>
                            @endif
                        </dd>

                        @if ($task->actual_start_date || $task->actual_end_date)
                            <dt class="col-5">{{ __('modules/task::task.actual_start') }}</dt>
                            <dd class="col-7">{{ $task->actual_start_date?->format('d/m/Y') ?? '—' }}</dd>
                            <dt class="col-5">{{ __('modules/task::task.actual_end') }}</dt>
                            <dd class="col-7">{{ $task->actual_end_date?->format('d/m/Y') ?? '—' }}</dd>
                        @endif

                        <dt class="col-5">{{ __('modules/task::task.time') }}</dt>
                        <dd class="col-7">{{ Task::formatHours($task->actual_hours) }} / {{ Task::formatHours($task->estimated_hours) }} {{ __('modules/task::task.hours_short') }}</dd>

                        <dt class="col-5">{{ __('modules/task::task.creator') }}</dt>
                        <dd class="col-7 mb-0">
                            {{ $task->createdBy?->name ?? '—' }}
                            <div class="small text-secondary">{{ $task->created_at?->format('d/m/Y H:i') }}</div>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</x-ui.layouts::app>
