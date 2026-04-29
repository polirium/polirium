<div>
    {{-- Filters --}}
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body py-2">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label small text-muted mb-1">{{ __('modules/task::task.filter_by_project') }}</label>
                            <select class="form-select form-select-sm" wire:model.live="filter_project_id">
                                <option value="">{{ __('core/base::general.all') }}</option>
                                @foreach($projects as $project)
                                    <option value="{{ $project->id }}">{{ $project->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-muted mb-1">{{ __('modules/task::task.assigned_to') }}</label>
                            <select class="form-select form-select-sm" wire:model.live="filter_assigned_to">
                                <option value="">{{ __('core/base::general.all') }}</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button class="btn btn-sm btn-ghost-danger" wire:click="clearFilters">
                                {!! tabler_icon('x', ['class' => 'icon icon-sm']) !!}
                                {{ __('core/base::general.clear_filter') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Kanban Board --}}
    <div class="kanban-board" x-data="kanbanBoard()" x-init="init()">
        <div class="row g-3">
            @foreach($columns as $column)
                <div class="col" style="min-width: 240px;">
                    <div class="kanban-column">
                        {{-- Column Header --}}
                        <div class="kanban-column-header bg-{{ $column['color'] }}-lt">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-{{ $column['color'] }}">
                                        {{ isset($tasks[$column['id']]) ? count($tasks[$column['id']]) : 0 }}
                                    </span>
                                    <span class="fw-semibold small">{{ $column['title'] }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Column Body (Sortable) --}}
                        <div class="kanban-column-body"
                             data-status="{{ $column['id'] }}"
                             id="kanban-col-{{ $column['id'] }}">

                            @foreach(($tasks[$column['id']] ?? []) as $task)
                                <div class="kanban-card" data-id="{{ $task->id }}" wire:key="task-{{ $task->id }}">
                                    <div class="card mb-2">
                                        <div class="card-body p-3">
                                            {{-- Priority Badge --}}
                                            @if($task->priority !== 'medium')
                                                <div class="mb-2">
                                                    @php
                                                        $colors = ['low' => 'muted', 'high' => 'warning', 'urgent' => 'danger'];
                                                        $color = $colors[$task->priority] ?? 'muted';
                                                    @endphp
                                                    <span class="badge bg-{{ $color }}-lt text-{{ $color }}">
                                                        {{ $task->priority_label }}
                                                    </span>
                                                </div>
                                            @endif

                                            {{-- Task Title --}}
                                            <h6 class="card-title mb-1" style="font-size: 0.875rem;">
                                                <a href="{{ route('admin.tasks.show', $task->id) }}"
                                                   class="text-reset text-decoration-none">
                                                    {{ $task->name }}
                                                </a>
                                            </h6>

                                            {{-- Task Code --}}
                                            <div class="text-muted mb-2" style="font-size: 0.75rem;">
                                                <code>{{ $task->code }}</code>
                                            </div>

                                            {{-- Description preview --}}
                                            @if($task->description)
                                                <p class="card-text text-muted mb-2" style="font-size: 0.75rem;">
                                                    {!! Str::limit(strip_tags($task->description), 60) !!}
                                                </p>
                                            @endif

                                            {{-- Dates --}}
                                            @if($task->planned_end_date)
                                                <div class="mb-2">
                                                    @php
                                                        $isOverdue = $task->is_overdue;
                                                        $dateColor = $isOverdue ? 'danger' : 'muted';
                                                    @endphp
                                                    <small class="text-{{ $dateColor }}">
                                                        {!! tabler_icon('calendar', ['class' => 'icon icon-sm']) !!}
                                                        {{ $task->planned_end_date->format('d/m/Y') }}
                                                        @if($isOverdue)
                                                            <span class="badge bg-danger-lt text-danger ms-1" style="font-size: 0.65rem;">
                                                                {{ __('modules/task::task.overdue') }}
                                                            </span>
                                                        @endif
                                                    </small>
                                                </div>
                                            @endif

                                            {{-- Progress --}}
                                            @if(($task->progress_percentage ?? 0) > 0)
                                                <div class="mb-2">
                                                    <div class="progress progress-sm mb-1" style="height: 4px;">
                                                        <div class="progress-bar"
                                                             style="width: {{ $task->progress_percentage }}%"
                                                             role="progressbar"></div>
                                                    </div>
                                                    <small class="text-muted" style="font-size: 0.7rem;">
                                                        {{ $task->progress_percentage }}%
                                                    </small>
                                                </div>
                                            @endif

                                            {{-- Footer: Project & Assignee --}}
                                            <div class="d-flex align-items-center justify-content-between mt-2">
                                                <div>
                                                    @if($task->project)
                                                        <span class="badge bg-primary-lt text-primary" style="font-size: 0.65rem;">
                                                            {!! Str::limit($task->project->name, 15) !!}
                                                        </span>
                                                    @endif
                                                </div>
                                                @if($task->assignedTo)
                                                    <div class="avatar avatar-xs rounded-circle"
                                                         title="{{ $task->assignedTo->name }}"
                                                         style="width: 24px; height: 24px; font-size: 0.6rem;">
                                                        <span class="avatar-placeholder">
                                                            {{ mb_substr($task->assignedTo->name, 0, 2) }}
                                                        </span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach

                            {{-- Empty State --}}
                            @if(empty($tasks[$column['id']]) || count($tasks[$column['id']]) === 0)
                                <div class="kanban-empty-state text-center py-4">
                                    <div class="text-muted" style="font-size: 0.8rem;">
                                        {!! tabler_icon('drag-drop', ['class' => 'icon icon-lg text-muted mb-2']) !!}
                                        <p class="mb-0">{{ __('modules/task::task.drag_to_add') }}</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- SortableJS + Alpine --}}
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
    <script>
        function kanbanBoard() {
            return {
                init() {
                    this.$nextTick(() => {
                        document.querySelectorAll('.kanban-column-body').forEach(column => {
                            if (column._sortable) return;
                            column._sortable = new Sortable(column, {
                                group: 'kanban',
                                animation: 200,
                                easing: 'cubic-bezier(0.25, 0.1, 0.25, 1)',
                                ghostClass: 'kanban-ghost',
                                dragClass: 'kanban-drag',
                                filter: '.kanban-empty-state',
                                onEnd: (evt) => {
                                    const taskId = evt.item.getAttribute('data-id');
                                    const newStatus = evt.to.getAttribute('data-status');

                                    if (taskId && newStatus) {
                                        @this.updateTaskStatus(parseInt(taskId), newStatus);

                                        const orderedIds = Array.from(evt.to.querySelectorAll('.kanban-card'))
                                            .map(child => parseInt(child.getAttribute('data-id')))
                                            .filter(id => !isNaN(id));
                                        @this.updateTaskOrder(orderedIds);
                                    }
                                }
                            });
                        });
                    });
                }
            }
        }
    </script>
    @endpush

    @push('styles')
    <style>
        .kanban-board {
            min-height: 500px;
            overflow-x: auto;
        }

        .kanban-board > .row {
            flex-wrap: nowrap;
        }

        .kanban-column {
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .kanban-column-header {
            padding: 0.75rem 1rem;
            border-radius: 0.5rem 0.5rem 0 0;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .kanban-column-body {
            flex: 1;
            padding: 0.75rem;
            background: var(--tblr-bg-surface);
            border: 1px solid var(--tblr-border-color);
            border-top: none;
            border-radius: 0 0 0.5rem 0.5rem;
            min-height: 350px;
            overflow-y: auto;
            max-height: calc(100vh - 320px);
        }

        .kanban-card {
            cursor: grab;
            transition: transform 0.2s ease-out, box-shadow 0.2s ease-out;
        }

        .kanban-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        .kanban-card:active {
            cursor: grabbing;
        }

        .kanban-card .card {
            border: 1px solid var(--tblr-border-color);
            transition: border-color 0.2s ease-out;
        }

        .kanban-card:hover .card {
            border-color: var(--tblr-primary);
        }

        .kanban-ghost {
            opacity: 0.3;
        }

        .kanban-drag {
            opacity: 0.9;
            transform: rotate(1deg);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .avatar-placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            background: var(--tblr-primary);
            color: white;
            font-weight: 600;
            border-radius: 50%;
        }

        .kanban-column-body::-webkit-scrollbar {
            width: 4px;
        }

        .kanban-column-body::-webkit-scrollbar-track {
            background: transparent;
        }

        .kanban-column-body::-webkit-scrollbar-thumb {
            background: transparent;
            border-radius: 2px;
            transition: background 0.2s;
        }

        .kanban-column-body:hover::-webkit-scrollbar-thumb {
            background: var(--tblr-border-color);
        }

        .kanban-empty-state {
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .kanban-board > .row > .col {
                min-width: 280px !important;
            }
        }
    </style>
    @endpush
</div>
