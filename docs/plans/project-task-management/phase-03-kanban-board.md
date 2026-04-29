# Phase 03: Kanban Board

**Date**: 2026-01-17
**Priority**: P2
**Estimated Effort**: 8 hours
**Status**: pending

---

## Context Links

**Dependencies**: Phase 01 (Database), Phase 02 (CRUD)
**Related Phases**: Phase 05 (Notifications)
**Research**: `research/researcher-02-ui-libraries.md` (Section 1: Kanban Libraries)

---

## Overview

Build drag-drop Kanban board using Livewire 4 native `wire:sort` directive for task management by status.

**Key Objectives**:
1. Create Kanban board component with status columns
2. Implement drag-drop using Livewire wire:sort
3. Add keyboard navigation (accessibility)
4. Support custom column configurations
5. Real-time status updates

---

## Key Insights from Research

### Livewire 4 `wire:sort` Directive
- **Source**: researcher-02-ui-libraries.md, Section 1.1
- **Native Support**: Built into Livewire 4 (as of Jan 2026)
- **Features**: Reordering, animations, ghost elements, cross-group dragging
- **Best For**: Kanban boards with status columns
- **Integration**: Zero dependencies, native Livewire

### Kanban Column Pattern
- **Typical Columns**: Backlog → To Do → In Progress → Review → Done
- **Customizable**: Per project/team
- **Drag-Drop**: Cross-group dragging enabled
- **State Persistence**: Save order in database

### Accessibility
- **WCAG Level A**: Keyboard navigation required
- **WAI-ARIA**: `aria-grabbed`, `aria-dropeffect`, `aria-label`
- **Touch Support**: Mobile-friendly drag-drop

---

## Requirements

### Functional Requirements

**FR-03.1**: Kanban Board Display
- Columns for each task status (backlog, todo, in_progress, review, done)
- Task cards with key info (name, assignee, priority, due date)
- Visual indicators (overdue, priority colors, assignee avatar)
- Column task counts
- Scrollable columns (independent scrolling)

**FR-03.2**: Drag-Drop Functionality
- Drag cards between columns (status change)
- Reorder cards within column (sort_order update)
- Drag handles for precise control
- Visual feedback during drag (ghost element)
- Drop target highlighting

**FR-03.3**: Task Actions from Card
- Quick edit (click card to open modal)
- Inline status change (right-click/context menu)
- Assignee change
- Priority change
- Delete with confirmation

**FR-03.4**: Customizable Columns
- Show/hide columns per project
- Custom column names
- Column limits (WIP limits)
- Filter by project

**FR-03.5**: Accessibility
- Keyboard navigation (Tab, Arrow keys, Enter/Space)
- Screen reader support
- Focus indicators
- ARIA labels

### Non-Functional Requirements

**NFR-03.1**: Performance smooth with 100+ cards
**NFR-03.2**: Mobile responsive (touch events)
**NFR-03.3**: Real-time updates (Livewire)
**NFR-03.4**: No external JS dependencies (use wire:sort)

---

## Architecture

### Component Structure

```
Platform\Modules\Project\Http\Livewire\
└── Task\
    └── Kanban\
        ├── TaskKanbanComponent.php
        ├── KanbanCardComponent.php
        └── KanbanColumnComponent.php
```

### View Structure

```
resources/views/task/
└── kanban/
    ├── index.blade.php
    ├── column.blade.php
    └── card.blade.php
```

### Database Updates

**Add sort_order column if not exists**:
```php
// Already added in Phase 01
$table->integer('sort_order')->default(0);
```

---

## Related Code Files

### Reference Files (Study These)

**Livewire wire:sort Documentation**:
- [Laracasts wire:sort examples](https://laracasts.com)
- [Livewire 4 documentation](https://livewire.laravel.com)

**Alpine.js Sort Plugin** (Fallback):
- [Alpine.js Sort Plugin](https://alpinejs.dev/plugins/sort)

### Files to Create

**Livewire Components**:
- `/Users/vingamagic/Developer/php/polirium/platform/modules/project/src/Http/Livewire/Task/Kanban/TaskKanbanComponent.php`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/project/src/Http/Livewire/Task/Kanban/KanbanColumnComponent.php`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/project/src/Http/Livewire/Task/Kanban/KanbanCardComponent.php`

**Blade Views**:
- `/Users/vingamagic/Developer/php/polirium/platform/modules/project/resources/views/task/kanban/index.blade.php`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/project/resources/views/task/kanban/column.blade.php`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/project/resources/views/task/kanban/card.blade.php`

---

## Implementation Steps

### Step 1: Verify Livewire 4 Version (0.5h)

**1.1 Check Livewire Version**:
```bash
php artisan about | grep Livewire
# Should show Livewire 4.x
```

**1.2 If not Livewire 4**:
- Upgrade Livewire: `composer require livewire/livewire:^4.0`
- Or use Alpine.js Sort Plugin as fallback

### Step 2: Create Kanban Component (3h)

**2.1 TaskKanbanComponent.php**:
```php
<?php

namespace Polirium\Modules\Project\Http\Livewire\Task\Kanban;

use Livewire\Component;
use Livewire\Attributes\Reactive;
use Polirium\Modules\Project\Http\Models\Task;
use Polirium\Modules\Project\Http\Models\Project;

class TaskKanbanComponent extends Component
{
    public $project_id = null;

    public $columns = [
        'backlog' => ['name' => 'Backlog', 'color' => 'gray'],
        'todo' => ['name' => 'To Do', 'color' => 'blue'],
        'in_progress' => ['name' => 'In Progress', 'color' => 'yellow'],
        'review' => ['name' => 'Review', 'color' => 'purple'],
        'done' => ['name' => 'Done', 'color' => 'green'],
    ];

    protected $listeners = [
        'task-updated' => '$refresh',
        'task-deleted' => '$refresh',
    ];

    public function mount(?int $project_id = null)
    {
        $this->project_id = $project_id;
    }

    public function getTasksProperty()
    {
        return Task::query()
            ->when($this->project_id, fn($q) => $q->where('project_id', $this->project_id))
            ->whereNull('parent_id') // Only root tasks in Kanban
            ->orderBy('sort_order')
            ->get()
            ->groupBy('status');
    }

    public function getProjectsProperty()
    {
        return Project::active()->pluck('name', 'id');
    }

    public function render()
    {
        return view('modules/project::task/kanban/index');
    }

    /**
     * Called when task is dropped in new column
     */
    public function onTaskDrop(int $task_id, string $new_status, int $new_order)
    {
        $task = Task::find($task_id);
        $task->status = $new_status;
        $task->sort_order = $new_order;

        // Auto-set dates based on status
        if ($new_status === 'in_progress' && !$task->actual_start_date) {
            $task->actual_start_date = now();
        } elseif ($new_status === 'done' && !$task->actual_end_date) {
            $task->actual_end_date = now();
            $task->progress_percentage = 100;
        }

        $task->save();

        // Reorder other tasks in column
        $this->reorderTasksInColumn($new_status, $task_id, $new_order);

        $this->dispatch('task-updated');
    }

    private function reorderTasksInColumn(string $status, int $moved_task_id, int $new_order)
    {
        $tasks = Task::where('status', $status)
            ->where('id', '!=', $moved_task_id)
            ->orderBy('sort_order')
            ->get();

        $order = 0;
        foreach ($tasks as $task) {
            if ($order === $new_order) {
                $order++; // Skip this position
            }
            $task->sort_order = $order++;
            $task->save();
        }
    }
}
```

### Step 3: Create Kanban Views (3h)

**3.1 Kanban Index View**:
```blade
<x-ui.layouts::app>
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        {!! tabler_icon('chart-gantt', ['class' => 'icon me-2']) !!}
                        {{ __('modules/project::task.kanban') }}
                    </h2>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <div class="btn-list">
                        {{-- Project Filter --}}
                        <select class="form-select" wire:model.live="project_id">
                            <option value="">{{ __('core/base::general.all_projects') }}</option>
                            @foreach($projects as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>

                        {{-- Add Task Button --}}
                        <button class="btn btn-primary"
                                wire:click="$dispatch('show-modal-create-task')">
                            {!! tabler_icon('plus', ['class' => 'icon']) !!}
                            {{ __('modules/project::task.create') }}
                        </button>

                        {{-- Table View Toggle --}}
                        <a href="{{ route('tasks.index') }}" class="btn btn-outline-secondary">
                            {!! tabler_icon('table', ['class' => 'icon']) !!}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Kanban Board --}}
    <div class="page-body">
        <div class="container-xl">
            <div class="kanban-board">
                <div class="row g-3" wire:sort>
                    @foreach($columns as $status => $column)
                        <div class="col-md-12 col-lg-{{ 12 / count($columns) }}">
                            @livewire('modules/project::task.kanban-column',
                                ['status' => $status, 'column' => $column, 'tasks' => $tasks[$status] ?? collect()],
                                key('kanban-column-' . $status)
                            )
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    @livewire('modules/project::modal-create-task')
</x-ui.layouts::app>

@push('styles')
<style>
.kanban-board {
    min-height: calc(100vh - 200px);
}

.kanban-column {
    min-height: 500px;
    max-height: calc(100vh - 250px);
    overflow-y: auto;
}

.kanban-card {
    cursor: grab;
    transition: all 0.2s;
}

.kanban-card:active {
    cursor: grabbing;
}

.kanban-card.dragging {
    opacity: 0.5;
    transform: rotate(3deg);
}

.kanban-column.drag-over {
    background-color: rgba(13, 110, 253, 0.1);
    border: 2px dashed rgba(13, 110, 253, 0.5);
}

/* Keyboard focus */
.kanban-card:focus {
    outline: 3px solid rgba(13, 110, 253, 0.5);
    outline-offset: 2px;
}
</style>
@endpush
```

**3.2 Kanban Column Component**:
```php
<?php

namespace Polirium\Modules\Project\Http\Livewire\Task\Kanban;

use Livewire\Component;

class KanbanColumnComponent extends Component
{
    public string $status;
    public array $column;
    public $tasks;

    public function getListeners()
    {
        return [
            'task-updated' => '$refresh',
            'echo:tasks,TaskUpdated' => '$refresh',
        ];
    }

    public function render()
    {
        $taskCount = $this->tasks?->count() ?? 0;

        return view('modules/project::task/kanban/column', [
            'taskCount' => $taskCount,
        ]);
    }
}
```

**3.3 Kanban Column View**:
```blade
<div class="kanban-column card" data-status="{{ $status }}">
    {{-- Column Header --}}
    <div class="card-header bg-{{ $column['color'] }}-lt">
        <div class="d-flex align-items-center justify-content-between">
            <h3 class="card-title mb-0">
                <span class="status-indicator bg-{{ $column['color'] }} me-2"></span>
                {{ __($column['name']) }}
            </h3>
            <span class="badge bg-{{ $column['color'] }} rounded-pill">{{ $taskCount }}</span>
        </div>
    </div>

    {{-- Column Body (Drop Zone) --}}
    <div class="card-body p-2 kanban-tasks"
         wire:sort:group="tasks"
         wire:sort:end="onTaskEnd">

        @if($tasks && $tasks->count() > 0)
            @foreach($tasks as $task)
                <div wire:sort:item="{{ $task->id }}"
                     tabindex="0"
                     role="button"
                     aria-label="{{ __('modules/project::task.task') }}: {{ $task->name }}"
                     class="kanban-card card mb-2">
                    @livewire('modules/project::task.kanban-card', ['task' => $task], key('task-' . $task->id))
                </div>
            @endforeach
        @else
            <div class="empty-state text-center py-5">
                <div class="empty-state-icon text-muted">
                    {!! tabler_icon('ghost', ['class' => 'icon icon-lg']) !!}
                </div>
                <p class="text-muted small">{{ __('modules/project::task.no_tasks') }}</p>
            </div>
        @endif
    </div>

    {{-- Column Footer (Add Task) --}}
    <div class="card-footer p-2">
        <button class="btn btn-sm btn-outline-primary w-100"
                wire:click="$dispatch('show-modal-create-task', ['status' => $status])">
            {!! tabler_icon('plus', ['class' => 'icon icon-sm']) !!}
            {{ __('modules/project::task.add_to_column') }}
        </button>
    </div>
</div>

@script
<script>
    // Handle drag end event
    $wire.on('task-end', (event) => {
        const { item, from, to, oldIndex, newIndex } = event.detail;

        // Get task ID from data attribute
        const taskId = item.querySelector('[data-task-id]')?.dataset.taskId;

        // Get new status from column
        const newStatus = to.closest('.kanban-column')?.dataset.status;

        if (taskId && newStatus) {
            @this.onTaskDrop(taskId, newStatus, newIndex);
        }
    });
</script>
@endscript
```

**3.4 Kanban Card Component**:
```php
<?php

namespace Polirium\Modules\Project\Http\Livewire\Task\Kanban;

use Livewire\Component;
use Polirium\Modules\Project\Http\Models\Task;

class KanbanCardComponent extends Component
{
    public Task $task;

    public function render()
    {
        return view('modules/project::task/kanban/card');
    }

    public function openEditModal()
    {
        $this->dispatch('show-modal-create-task', ['id' => $this->task->id]);
    }

    public function changeStatus(string $newStatus)
    {
        $this->task->status = $newStatus;
        $this->task->save();

        $this->dispatch('task-updated');
    }

    public function deleteTask()
    {
        $this->task->delete();
        $this->dispatch('task-deleted');
    }
}
```

**3.5 Kanban Card View**:
```blade
<div class="card-body p-3" data-task-id="{{ $task->id }}">
    {{-- Priority Indicator --}}
    <div class="priority-indicator priority-{{ $task->priority }}"></div>

    {{-- Task Title --}}
    <h6 class="card-title mb-2">{{ $task->name }}</h6>

    {{-- Task Meta --}}
    <div class="d-flex align-items-center gap-2 mb-2">
        {{-- Status Badge --}}
        <span class="badge bg-{{ $task->status === 'done' ? 'success' : ($task->is_overdue ? 'danger' : 'secondary') }} badge-sm">
            {{ $task->status_label }}
        </span>

        {{-- Priority Badge --}}
        <span class="badge bg-{{ $task->priority === 'urgent' ? 'danger' : ($task->priority === 'high' ? 'warning' : 'light') }} badge-sm">
            {{ $task->priority_label }}
        </span>
    </div>

    {{-- Due Date --}}
    @if($task->planned_end_date)
        <div class="d-flex align-items-center gap-1 small text-muted mb-2">
            {!! tabler_icon('calendar', ['class' => 'icon icon-sm']) !!}
            <span class="{{ $task->is_overdue ? 'text-danger' : '' }}">
                {{ $task->planned_end_date->format('d/m/Y') }}
            </span>
            @if($task->is_overdue)
                <span class="badge bg-danger badge-sm">{{ __('modules/project::task.overdue') }}</span>
            @endif
        </div>
    @endif

    {{-- Assignee --}}
    @if($task->assignedTo)
        <div class="d-flex align-items-center gap-2 mb-2">
            <div class="avatar avatar-sm">
                <img src="{{ $task->assignedTo->avatar_url ?? asset('images/default-avatar.png') }}"
                     alt="{{ $task->assignedTo->name }}">
            </div>
            <span class="small text-muted">{{ $task->assignedTo->name }}</span>
        </div>
    @endif

    {{-- Progress Bar --}}
    @if($task->progress_percentage > 0)
        <div class="progress progress-sm mb-0">
            <div class="progress-bar bg-{{ $task->progress_percentage == 100 ? 'success' : 'primary' }}"
                 role="progressbar"
                 style="width: {{ $task->progress_percentage }}%"
                 aria-valuenow="{{ $task->progress_percentage }}"
                 aria-valuemin="0"
                 aria-valuemax="100"></div>
        </div>
        <span class="small text-muted">{{ number_format($task->progress_percentage) }}%</span>
    @endif

    {{-- Action Menu (Hover) --}}
    <div class="dropdown card-dropdown">
        <button type="button"
                class="btn btn-icon btn-sm dropdown-toggle"
                data-bs-toggle="dropdown"
                aria-expanded="false">
            {!! tabler_icon('dots', ['class' => 'icon']) !!}
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
            <li>
                <a class="dropdown-item d-flex align-items-center gap-2"
                   wire:click="openEditModal">
                    {!! tabler_icon('pencil', ['class' => 'icon icon-sm']) !!}
                    {{ __('core/base::general.edit') }}
                </a>
            </li>
            <li>
                <a class="dropdown-item d-flex align-items-center gap-2"
                   wire:click="deleteTask"
                   wire:confirm="{{ __('core/base::general.confirm_delete') }}">
                    {!! tabler_icon('trash', ['class' => 'icon icon-sm']) !!}
                    {{ __('core/base::general.delete') }}
                </a>
            </li>
        </ul>
    </div>
</div>

@push('styles')
<style>
.priority-indicator {
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    border-radius: 4px 0 0 4px;
}

.priority-urgent { background-color: #dc3545; }
.priority-high { background-color: #ffc107; }
.priority-medium { background-color: #0d6efd; }
.priority-low { background-color: #6c757d; }

.card-dropdown {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    opacity: 0;
    transition: opacity 0.2s;
}

.kanban-card:hover .card-dropdown {
    opacity: 1;
}

.status-indicator {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
}
</style>
@endpush
```

### Step 4: Add Route (0.5h)

**4.1 Add Kanban Route**:
```php
// routes/web.php
Route::get('kanban', 'TaskController@kanban')
    ->name('kanban')
    ->middleware('can:tasks.index');
```

**4.2 Add Controller Method**:
```php
// TaskController.php
public function kanban()
{
    return view('modules/project::task/kanban/index');
}
```

### Step 5: Test Kanban Board (1h)

**5.1 Test Checklist**:
- [ ] Board displays all columns
- [ ] Tasks appear in correct columns
- [ ] Drag card between columns (status changes)
- [ ] Reorder cards within column (sort_order updates)
- [ ] Click card opens edit modal
- [ ] Project filter works
- [ ] Keyboard navigation (Tab, Enter)
- [ ] Mobile touch drag-drop
- [ ] Real-time updates (multiple users)

**5.2 Test Scenarios**:
1. Drag task from "To Do" to "In Progress"
   - Verify: Status updates, actual_start_date set
2. Drag task from "In Progress" to "Done"
   - Verify: Status updates, actual_end_date set, progress = 100%
3. Reorder tasks within column
   - Verify: sort_order updates for all affected tasks
4. Filter by project
   - Verify: Only project tasks shown
5. Delete task from card menu
   - Verify: Task removed, no cascade issues

---

## Todo List

### Components
- [ ] Create TaskKanbanComponent.php
- [ ] Create KanbanColumnComponent.php
- [ ] Create KanbanCardComponent.php
- [ ] Create kanban/index.blade.php
- [ ] Create kanban/column.blade.php
- [ ] Create kanban/card.blade.php

### Functionality
- [ ] Implement wire:sort for drag-drop
- [ ] Handle onTaskEnd event
- [ ] Update task status on drop
- [ ] Reorder tasks in column
- [ ] Auto-set dates on status change
- [ ] Add project filter

### UI/UX
- [ ] Style column headers with colors
- [ ] Add task count badges
- [ ] Style kanban cards
- [ ] Add priority indicators
- [ ] Show overdue badges
- [ ] Display assignee avatars
- [ ] Add progress bars

### Accessibility
- [ ] Add tabindex to cards
- [ ] Add ARIA labels
- [ ] Test keyboard navigation
- [ ] Test screen reader support
- [ ] Add focus indicators

### Testing
- [ ] Test drag-drop between columns
- [ ] Test reorder within column
- [ ] Test project filter
- [ ] Test edit modal from card
- [ ] Test delete from card menu
- [ ] Test mobile touch events

---

## Success Criteria

### Drag-Drop
- [ ] Cards can be dragged between columns
- [ ] Status updates when dropped
- [ ] Sort order persists after refresh
- [ ] Visual feedback during drag (ghost element)
- [ ] Drop zone highlights

### Status Management
- [ ] actual_start_date set when moved to "in_progress"
- [ ] actual_end_date set when moved to "done"
- [ ] progress = 100% when marked done
- [ ] Task counts update per column

### Accessibility
- [ ] Tab key navigates between cards
- [ ] Enter/Space opens card
- [ ] ARIA labels describe actions
- [ ] Focus visible on all interactive elements

### Performance
- [ ] Board loads in <2s with 100 tasks
- [ ] Drag-drop is smooth (60fps)
- [ ] No memory leaks after extended use

---

## Risk Assessment

| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| wire:sort not available in Livewire 3.x | High | Low | Alpine.js Sort Plugin fallback |
| Mobile drag-drop issues | Medium | High | Test touch events, add swipe actions |
| Performance with 500+ tasks | Medium | Medium | Virtual scrolling, pagination |
| Real-time sync conflicts | Low | Low | Livewire defer, debounce |
| Subtasks not visible in Kanban | Medium | High | Show subtask count, expandable cards |

---

## Security Considerations

1. **Authorization**: Check permissions before status changes
2. **Validation**: Validate status transitions
3. **CSRF**: Livewire handles automatically
4. **SQL Injection**: Use Eloquent, not raw queries

---

## Next Steps

**Phase 04** (Gantt Chart):
1. Install DHTMLX Gantt library
2. Create Gantt view component
3. Add API endpoints for task data
4. Implement drag-drop rescheduling

**Phase 05** (Progress & Notifications):
1. Calculate progress from subtasks
2. Detect overdue tasks
3. Send notifications for delays
4. Add dashboard widgets

---

## Unresolved Questions

1. Should subtasks be shown in Kanban (nested cards) or hidden?
2. How to handle WIP (Work In Progress) limits per column?
3. Should we support swimlanes (group by assignee)?
4. How to handle archived/cancelled tasks in Kanban?

---

**Last Updated**: 2026-01-17
**Next Review**: After basic drag-drop working
**Blocking**: Phase 01, Phase 02
