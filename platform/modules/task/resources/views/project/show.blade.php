<x-ui.layouts::app>
    <x-slot:title>{{ $project->name }}</x-slot:title>
    <x-slot:subtitle>
        <span class="badge bg-{{ $project->status_color }}-lt">{{ $project->status_label }}</span>
        <code class="ms-1">{{ $project->code }}</code>
        @if ($project->is_overdue)
            <span class="badge bg-danger-lt ms-1">{{ __('modules/task::task.overdue') }}</span>
        @endif
    </x-slot:subtitle>
    <x-slot:actions>
        <x-ui::button color="secondary" outline icon="arrow-left" :href="route('admin.projects.index')">
            {{ __('core/base::general.back') }}
        </x-ui::button>
        @can('projects.destroy')
            <form method="POST" action="{{ route('admin.projects.destroy', $project->id) }}" class="d-inline"
                  onsubmit="return confirm(@js(__('modules/task::project.confirm_delete')))">
                @csrf
                @method('DELETE')
                <x-ui::button color="danger" outline icon="trash" type="submit">
                    {{ __('core/base::general.delete') }}
                </x-ui::button>
            </form>
        @endcan
        @can('projects.edit')
            <x-ui::button color="secondary" outline icon="pencil" :href="route('admin.projects.edit', $project->id)">
                {{ __('core/base::general.edit') }}
            </x-ui::button>
        @endcan
        @can('tasks.create')
            <x-ui::button color="primary" icon="plus" :href="route('admin.tasks.create', ['project_id' => $project->id])">
                {{ __('modules/task::project.add_task') }}
            </x-ui::button>
        @endcan
    </x-slot:actions>

    @include('modules/task::partials.alerts')

    {{-- Summary --}}
    <div class="row row-cards mb-3">
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="subheader">{{ __('modules/task::project.progress') }}</div>
                    <div class="h1 mb-2">{{ $project->progress }}%</div>
                    <div class="progress progress-sm">
                        <div class="progress-bar bg-{{ $project->progress >= 100 ? 'success' : 'primary' }}" style="width: {{ $project->progress }}%"></div>
                    </div>
                    <div class="text-secondary small mt-1">{{ __('modules/task::project.progress_hint') }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="subheader">{{ __('modules/task::project.total_tasks') }}</div>
                    <div class="h1 mb-0">{{ $stats['total'] }}</div>
                    <div class="text-secondary">{{ __('modules/task::project.done_tasks') }}: {{ $stats['done'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="subheader">{{ __('modules/task::project.overdue_tasks') }}</div>
                    <div class="h1 mb-0 {{ $stats['overdue'] ? 'text-danger' : '' }}">{{ $stats['overdue'] }}</div>
                    <div class="text-secondary">
                        {{ $project->planned_end_date ? __('modules/task::task.end_date') . ': ' . $project->planned_end_date->format('d/m/Y') : '—' }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="subheader">{{ __('modules/task::project.hours') }}</div>
                    <div class="h1 mb-0">{{ \Polirium\Modules\Task\Models\Task::formatHours($stats['actual_hours']) }} / {{ \Polirium\Modules\Task\Models\Task::formatHours($stats['estimated_hours']) }}</div>
                    <div class="text-secondary">{{ __('modules/task::task.budget') }}: {{ core_number_format((float) $project->budget) }} VNĐ</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        {{-- Tasks --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ __('modules/task::project.tasks') }}</h3>
                    <div class="card-actions d-flex flex-wrap gap-1">
                        @foreach ($stats['by_status'] as $status => $count)
                            @if ($count)
                                <span class="badge bg-{{ \Polirium\Modules\Task\Models\Task::STATUS_COLORS[$status] }}-lt">{{ __('modules/task::status.' . $status) }}: {{ $count }}</span>
                            @endif
                        @endforeach
                    </div>
                </div>
                @php
                    $byParent = $project->tasks->groupBy(fn ($task) => $project->tasks->contains('id', $task->parent_id) ? $task->parent_id : 0);
                    $rows = [];
                    $walk = function ($parentId, $depth) use (&$walk, &$rows, $byParent) {
                        foreach ($byParent->get($parentId, collect()) as $task) {
                            $rows[] = [$task, $depth];
                            $walk($task->id, $depth + 1);
                        }
                    };
                    $walk(0, 0);
                @endphp
                @if ($rows)
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th>{{ __('modules/task::task.task') }}</th>
                                    <th>{{ __('modules/task::task.status') }}</th>
                                    <th>{{ __('modules/task::task.assignee') }}</th>
                                    <th>{{ __('modules/task::task.end_date') }}</th>
                                    <th class="w-25">{{ __('modules/task::task.progress') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rows as [$task, $depth])
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center" style="padding-left: {{ $depth * 1.25 }}rem">
                                                @if ($depth)
                                                    <span class="text-secondary me-1">{!! tabler_icon('subtask', ['class' => 'icon icon-sm']) !!}</span>
                                                @endif
                                                <div>
                                                    <a href="{{ route('admin.tasks.show', $task->id) }}" class="fw-medium">{{ $task->name }}</a>
                                                    <div class="text-secondary small">
                                                        {{ $task->code }}
                                                        <span class="badge bg-{{ $task->priority_color }}-lt ms-1">{{ $task->priority_label }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="badge bg-{{ $task->status_color }}-lt">{{ $task->status_label }}</span></td>
                                        <td class="text-secondary">{{ $task->assignedTo?->name ?? __('modules/task::task.unassigned') }}</td>
                                        <td class="{{ $task->is_overdue ? 'text-danger fw-medium' : 'text-secondary' }}">
                                            {{ $task->planned_end_date?->format('d/m/Y') ?? '—' }}
                                        </td>
                                        <td>
                                            @php($progress = $task->status === 'done' ? 100 : $task->progress)
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="progress progress-sm flex-fill">
                                                    <div class="progress-bar bg-{{ $progress >= 100 ? 'success' : 'primary' }}" style="width: {{ $progress }}%"></div>
                                                </div>
                                                <span class="small text-secondary">{{ $progress }}%</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="card-body">
                        <div class="empty py-4">
                            <div class="empty-icon">{!! tabler_icon('checklist', ['class' => 'icon']) !!}</div>
                            <p class="empty-title">{{ __('modules/task::task.no_tasks') }}</p>
                            <p class="empty-subtitle text-secondary">{{ __('modules/task::project.no_tasks_hint') }}</p>
                            @can('tasks.create')
                                <div class="empty-action">
                                    <a href="{{ route('admin.tasks.create', ['project_id' => $project->id]) }}" class="btn btn-primary">
                                        {!! tabler_icon('plus', ['class' => 'icon']) !!} {{ __('modules/task::project.add_task') }}
                                    </a>
                                </div>
                            @endcan
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Details --}}
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">{{ __('modules/task::task.project_info') }}</h3></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-5">{{ __('modules/task::task.priority') }}</dt>
                        <dd class="col-7">{{ $project->priority_label }}</dd>
                        <dt class="col-5">{{ __('modules/task::task.start_date') }}</dt>
                        <dd class="col-7">{{ $project->planned_start_date?->format('d/m/Y') ?? '—' }}</dd>
                        <dt class="col-5">{{ __('modules/task::task.end_date') }}</dt>
                        <dd class="col-7">{{ $project->planned_end_date?->format('d/m/Y') ?? '—' }}</dd>
                        <dt class="col-5">{{ __('modules/task::task.branch') }}</dt>
                        <dd class="col-7">{{ $project->branch?->name ?? '—' }}</dd>
                        <dt class="col-5">{{ __('modules/task::task.creator') }}</dt>
                        <dd class="col-7">{{ $project->createdBy?->name ?? '—' }}</dd>
                        <dt class="col-5">{{ __('modules/task::task.created_at') }}</dt>
                        <dd class="col-7 mb-0">{{ $project->created_at?->format('d/m/Y H:i') }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title">{{ __('modules/task::task.description') }}</h3></div>
                <div class="card-body">
                    @if ($project->description)
                        <div class="text-break">{!! nl2br(e($project->description)) !!}</div>
                    @else
                        <p class="text-secondary mb-0">{{ __('modules/task::task.no_description') }}</p>
                    @endif
                    @if ($project->note)
                        <hr class="my-3">
                        <div class="text-secondary small">{{ __('modules/task::task.note') }}: {{ $project->note }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-ui.layouts::app>
