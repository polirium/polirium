# Phase 04: Gantt Chart (Custom UI)

**Date**: 2026-01-17
**Priority**: P1
**Estimated Effort**: 14 hours
**Status**: pending

---

## Context Links

**Dependencies**: Phase 01 (Database), Phase 02 (CRUD)
**Related Phases**: Phase 03 (Kanban), Phase 05 (Progress)
**Research**: `research/researcher-02-ui-libraries.md` (Section 2: Gantt alternatives)

---

## Overview

Build **custom HTML/CSS/Alpine.js Gantt chart UI** (100% open source, no licensing constraints) for timeline visualization with drag-drop rescheduling and dependency management.

**Key Objectives**:
1. Design responsive timeline layout with HTML/CSS Grid
2. Implement task bars with drag-drop (Alpine.js)
3. Display dependency arrows with SVG
4. Support zoom levels (day/week/month views)
5. Add critical path highlighting

---

## Key Insights

### Why Custom UI?
- **No licensing**: 100% MIT/BSD - fully open source
- **Full control**: Customize everything, no black-box
- **Polirium patterns**: Follow existing Tabler UI/Alpine.js patterns
- **Lightweight**: Only necessary features, no bloat

### Reference Implementations
- **Frappe Gantt** (MIT): https://github.com/frappe/gantt
- **Gantt.js** (MIT): https://github.com/frappe/gantt-js
- **jQuery Gantt**: Study patterns, not dependencies

### Tech Stack
- **HTML**: Semantic structure, accessibility
- **CSS**: Grid layout, Tabler variables, custom styles
- **Alpine.js**: Drag-drop, state management, events
- **Livewire**: Backend sync, data loading
- **SVG**: Dependency arrows

---

## Requirements

### Functional Requirements

**FR-04.1**: Timeline Display
- Tasks as horizontal bars positioned by date
- Time scale header (days/weeks/months)
- Task tree in left panel (collapsible)
- Today line (vertical marker)
- Weekend highlighting

**FR-04.2**: Task Operations
- Drag task bar to reschedule (Alpine.js x-drag)
- Resize handle to change duration
- Click task to edit (Livewire modal)
- Color by status/assignee

**FR-04.3**: Dependencies
- SVG arrows between tasks
- 4 types: FS, SS, FF, SF
- Delete via click
- Prevent circular dependencies

**FR-04.4**: Zoom & Navigation
- Day/Week/Month zoom levels
- Horizontal scroll sync
- Scroll to task
- Collapse/expand subtasks

### Non-Functional Requirements

**NFR-04.1**: Performance < 2s load with 200 tasks
**NFR-04.2**: Mobile responsive (horizontal scroll)
**NFR-04.3**: Zero external dependencies (except Alpine/Livewire)

---

## Architecture

### Component Structure

```
Platform\Modules\Project\Http\Livewire\
└── Project\
    └── Gantt\
        ├── ProjectGanttComponent.php       # Main Livewire component
        ├── GanttDataTrait.php               # Data loading logic
        └── GanttSyncTrait.php               # Backend sync
```

### View Structure

```
resources/views/project/
└── gantt/
    ├── index.blade.php                     # Main Gantt view
    ├── components/
    │   ├── timeline.blade.php              # Timeline grid
    │   ├── task-bar.blade.php              # Single task bar
    │   ├── dependency-arrow.blade.php      # SVG arrow
    │   └── task-tree.blade.php             # Left panel tree
    └── partials/
        └── _gantt-styles.blade.php         # Custom CSS
```

### Custom CSS File

```
resources/assets/scss/polirium/
└── gantt-chart.scss                        # Gantt-specific styles
```

---

## Related Code Files

### Reference Files (Study Patterns)

**Existing Polirium Patterns**:
- `/platform/modules/vendor/resources/views/transfer/index/index.blade.php` (Layout)
- `/platform/core/ui/resources/assets/scss/polirium/` (CSS patterns)
- Tabler documentation (Grid, cards, utilities)

**Open Source Gantt References**:
- https://github.com/frappe/gantt (Frappe Gantt - MIT)
- https://github.com/dbgantt/gantt (Study algorithms)

### Files to Create

**Livewire**:
- `platform/modules/project/src/Http/Livewire/Project/Gantt/ProjectGanttComponent.php`

**Views**:
- `platform/modules/project/resources/views/project/gantt/index.blade.php`
- `platform/modules/project/resources/views/project/gantt/components/timeline.blade.php`

**Styles**:
- `platform/core/ui/resources/assets/scss/polirium/gantt-chart.scss`

**JS (Alpine component)**:
- `platform/modules/project/resources/assets/js/gantt-chart.js`

---

## Implementation Steps

### Step 1: Design CSS Grid Layout (3h)

**1.1 Create gantt-chart.scss**:
```scss
// platform/core/ui/resources/assets/scss/polirium/gantt-chart.scss

.gantt-chart {
  display: grid;
  grid-template-columns: 300px 1fr;
  height: calc(100vh - 200px);
  border: 1px solid var(--tblr-border-color);
  border-radius: var(--tblr-border-radius);
  overflow: hidden;
}

// Left panel - task tree
.gantt-task-tree {
  background: var(--tblr-bg-surface);
  border-right: 1px solid var(--tblr-border-color);
  overflow-y: auto;
  user-select: none;

  .task-row {
    padding: 0.5rem 1rem;
    border-bottom: 1px solid var(--tblr-border-color);
    cursor: pointer;

    &:hover {
      background: var(--tblr-bg-surface-hover);
    }

    &.active {
      background: var(--tblr-primary-bg-subtle);
    }
  }

  .task-indent {
    padding-left: 1rem;
  }

  .task-toggle {
    cursor: pointer;
    margin-right: 0.5rem;
  }
}

// Right panel - timeline
.gantt-timeline {
  display: grid;
  grid-template-rows: auto 1fr;
  overflow-x: auto;
  overflow-y: hidden;
  position: relative;

  // Sync scroll
  &.syncing {
    pointer-events: none;
  }
}

// Timeline header
.gantt-timeline-header {
  display: grid;
  position: sticky;
  top: 0;
  background: var(--tblr-bg-surface);
  border-bottom: 1px solid var(--tblr-border-color);
  z-index: 10;
}

.gantt-time-cell {
  padding: 0.5rem;
  text-align: center;
  border-right: 1px solid var(--tblr-border-color);
  font-size: 0.75rem;
  font-weight: 500;

  &.weekend {
    background: var(--tblr-bg-surface-subtle);
  }

  &.today {
    background: var(--tblr-primary-bg-subtle);
    color: var(--tblr-primary);
  }
}

// Timeline body
.gantt-timeline-body {
  position: relative;
  overflow-y: auto;
}

.gantt-row {
  height: 40px;
  border-bottom: 1px solid var(--tblr-border-color);
  position: relative;

  &.weekend {
    background: var(--tblr-bg-surface-subtle);
  }
}

// Today line
.gantt-today-line {
  position: absolute;
  top: 0;
  bottom: 0;
  width: 2px;
  background: var(--tblr-danger);
  z-index: 5;
}

// Task bar
.gantt-task-bar {
  position: absolute;
  height: 24px;
  top: 8px;
  border-radius: 4px;
  cursor: move;
  display: flex;
  align-items: center;
  padding: 0 0.5rem;
  font-size: 0.75rem;
  color: white;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);

  // Drag handle
  &::after {
    content: '';
    position: absolute;
    right: 0;
    top: 0;
    bottom: 0;
    width: 8px;
    cursor: ew-resize;
    background: rgba(0,0,0,0.1);
  }

  // Status colors
  &.status-backlog { background: #6c757d; }
  &.status-todo { background: #0d6efd; }
  &.status-in_progress { background: #ffc107; color: #000; }
  &.status-review { background: #6f42c1; }
  &.status-done { background: #198754; }
  &.status-cancelled { background: #dc3545; }

  // Priority indicator
  &.priority-urgent {
    border-left: 4px solid #dc3545;
  }
}

// Dependency arrow SVG
.gantt-dependencies {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
  z-index: 3;
}

.gantt-arrow {
  stroke: #6c757d;
  stroke-width: 2;
  fill: none;

  &.critical {
    stroke: #dc3545;
  }

  circle {
    fill: #fff;
    stroke: #6c757d;
    stroke-width: 2;
  }
}

// Zoom levels
.gantt-zoom-day .gantt-time-cell {
  min-width: 40px;
}

.gantt-zoom-week .gantt-time-cell {
  min-width: 120px;
}

.gantt-zoom-month .gantt-time-cell {
  min-width: 400px;
}

// Responsive
@media (max-width: 768px) {
  .gantt-chart {
    grid-template-columns: 200px 1fr;
  }

  .gantt-task-tree {
    font-size: 0.75rem;
  }
}
```

**1.2 Add to app.scss**:
```scss
// platform/core/ui/resources/assets/scss/app.scss
@import "./polirium/gantt-chart.scss";
```

**1.3 Build CSS**:
```bash
cd /Users/vingamagic/Developer/php/polirium
npm run dev
```

### Step 2: Create Livewire Component (3h)

**2.1 ProjectGanttComponent.php**:
```php
<?php

namespace Polirium\Modules\Project\Http\Livewire\Project\Gantt;

use Livewire\Component;
use Livewire\WithPagination;
use Polirium\Modules\Project\Http\Models\{Project, Task, TaskDependency};
use Carbon\Carbon;

class ProjectGanttComponent extends Component
{
    use WithPagination, GanttDataTrait, GanttSyncTrait;

    // Filters
    public $project_filter = null;
    public $status_filter = null;
    public $assignee_filter = null;

    // View state
    public $zoom = 'week'; // day, week, month
    public $view_start_date;
    public $view_end_date;
    public $expanded_tasks = [];

    // Editing
    public $editing_task = null;
    public $show_task_modal = false;

    public function mount()
    {
        $this->view_start_date = now()->startOfWeek();
        $this->view_end_date = now()->addWeek()->endOfWeek();
    }

    public function render()
    {
        $tasks = $this->loadTasks();
        $dependencies = $this->loadDependencies();

        return view('modules/project::project/gantt/index', [
            'tasks' => $tasks,
            'dependencies' => $dependencies,
            'timeline_days' => $this->getTimelineDays(),
            'projects' => Project::active()->pluck('name', 'id'),
        ]);
    }

    /**
     * Get tasks for Gantt chart
     */
    private function loadTasks()
    {
        return Task::query()
            ->when($this->project_filter, fn($q) => $q->where('project_id', $this->project_filter))
            ->when($this->status_filter, fn($q) => $q->where('status', $this->status_filter))
            ->when($this->assignee_filter, fn($q) => $q->where('assigned_to', $this->assignee_filter))
            ->with(['project', 'assignee', 'children', 'parent'])
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Get dependencies
     */
    private function loadDependencies()
    {
        return TaskDependency::with(['predecessor', 'successor'])->get();
    }

    /**
     * Get timeline days based on zoom level
     */
    public function getTimelineDays(): array
    {
        $days = [];
        $current = $this->view_start_date->copy();
        $end = $this->view_end_date;

        while ($current <= $end) {
            $days[] = [
                'date' => $current->copy(),
                'is_weekend' => $current->isWeekend(),
                'is_today' => $current->isToday(),
                'label' => $this->getDayLabel($current),
            ];
            $current->addDay();
        }

        return $days;
    }

    /**
     * Get day label based on zoom
     */
    private function getDayLabel(Carbon $date): string
    {
        return match ($this->zoom) {
            'day' => $date->format('d/m'),
            'week' => $date->format('D'),
            'month' => $date->format('W'),
            default => $date->format('d/m'),
        };
    }

    /**
     * Change zoom level
     */
    public function setZoom(string $zoom)
    {
        $this->zoom = $zoom;

        match ($zoom) {
            'day' => $this->view_end_date = $this->view_start_date->copy()->addDays(14),
            'week' => $this->view_end_date = $this->view_start_date->copy()->addWeeks(4),
            'month' => $this->view_end_date = $this->view_start_date->copy()->addMonths(3),
        };
    }

    /**
     * Navigate timeline
     */
    public function navigate(string $direction)
    {
        $amount = match ($this->zoom) {
            'day' => 7,
            'week' => 1,
            'month' => 1,
            default => 7,
        };

        $unit = match ($this->zoom) {
            'day' => 'days',
            'week' => 'weeks',
            'month' => 'months',
            default => 'days',
        };

        if ($direction === 'prev') {
            $this->view_start_date->sub($amount, $unit);
        } else {
            $this->view_start_date->add($amount, $unit);
        }

        $this->setZoom($this->zoom);
    }

    /**
     * Toggle task expansion
     */
    public function toggleTask(int $taskId)
    {
        if (in_array($taskId, $this->expanded_tasks)) {
            $this->expanded_tasks = array_diff($this->expanded_tasks, [$taskId]);
        } else {
            $this->expanded_tasks[] = $taskId;
        }
    }

    /**
     * Open task edit modal
     */
    public function editTask(int $taskId)
    {
        $this->editing_task = Task::find($taskId);
        $this->show_task_modal = true;
    }

    /**
     * Update task from drag-drop
     */
    public function updateTaskPosition(array $data)
    {
        $task = Task::find($data['id']);

        if ($task && auth()->user()->can('update', $task)) {
            $task->update([
                'planned_start_date' => Carbon::parse($data['start_date']),
                'planned_end_date' => Carbon::parse($data['end_date']),
            ]);

            $this->dispatch('task-updated');
        }
    }
}
```

**2.2 Create Traits** (gantt-data-trait.php, gantt-sync-trait.php):
```php
<?php

namespace Polirium\Modules\Project\Http\Livewire\Project\Gantt;

trait GanttDataTrait
{
    // Helper methods for data formatting
    public function getTaskPosition(Task $task): array
    {
        $timelineStart = $this->view_start_date;
        $taskStart = $task->planned_start_date ?? now();
        $taskEnd = $task->planned_end_date ?? now()->addDay();

        $dayWidth = match ($this->zoom) {
            'day' => 40,
            'week' => 20,
            'month' => 15,
            default => 20,
        };

        $offsetDays = $timelineStart->diffInDays($taskStart, false);
        $durationDays = $taskStart->diffInDays($taskEnd) + 1;

        return [
            'left' => max(0, $offsetDays * $dayWidth),
            'width' => $durationDays * $dayWidth,
        ];
    }
}

trait GanttSyncTrait
{
    // Backend sync methods
    public function syncTaskChanges(array $changes)
    {
        foreach ($changes as $change) {
            $this->updateTaskPosition($change);
        }
    }
}
```

### Step 3: Create Blade Views (4h)

**3.1 index.blade.php** (Main Gantt view):
```blade
<x-ui.layouts::app>
    @push('styles')
        @include('modules.project::gantt.partials._gantt-styles')
    @endpush

    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        {!! tabler_icon('chart-gantt', ['class' => 'icon me-2']) !!}
                        {{ __('modules/project::project.gantt') }}
                    </h2>
                </div>
                <div class="col-auto ms-auto">
                    <div class="btn-list">
                        {{-- Filters }}
                        <select class="form-select" wire:model.live="project_filter">
                            <option value="">{{ __('core/base::general.all') }}</option>
                            @foreach($projects as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>

                        {{-- Zoom controls }}
                        <div class="btn-group">
                            <button class="btn {{ $zoom === 'day' ? 'btn-primary' : 'btn-outline-secondary' }}"
                                    wire:click="setZoom('day')">
                                {{ __('modules/project::gantt.zoom_day') }}
                            </button>
                            <button class="btn {{ $zoom === 'week' ? 'btn-primary' : 'btn-outline-secondary' }}"
                                    wire:click="setZoom('week')">
                                {{ __('modules/project::gantt.zoom_week') }}
                            </button>
                            <button class="btn {{ $zoom === 'month' ? 'btn-primary' : 'btn-outline-secondary' }}"
                                    wire:click="setZoom('month')">
                                {{ __('modules/project::gantt.zoom_month') }}
                            </button>
                        </div>

                        {{-- Navigation --}}
                        <button class="btn btn-outline-secondary" wire:click="navigate('prev')">
                            {!! tabler_icon('chevron-left') !!}
                        </button>
                        <button class="btn btn-outline-secondary" wire:click="navigate('next')">
                            {!! tabler_icon('chevron-right') !!}
                        </button>

                        {{-- View toggle }}
                        <a href="{{ route('projects.index') }}" class="btn btn-outline-secondary">
                            {!! tabler_icon('table') !!}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            {{-- Gantt Chart Component --}}
            <div x-data="ganttChart()" class="gantt-chart gantt-zoom-{{ $zoom }}">
                {{-- Left: Task Tree --}}
                <div class="gantt-task-tree">
                    @foreach($tasks->whereNull('parent_id') as $task)
                        @include('modules.project::gantt.components.task-tree', ['task' => $task, 'level' => 0])
                    @endforeach
                </div>

                {{-- Right: Timeline --}}
                <div class="gantt-timeline">
                    {{-- Header }}
                    <div class="gantt-timeline-header" style="grid-template-columns: repeat({{ count($timeline_days) }}, minmax(40px, 1fr));">
                        @foreach($timeline_days as $day)
                            <div class="gantt-time-cell {{ $day['is_weekend'] ? 'weekend' : '' }} {{ $day['is_today'] ? 'today' : '' }}">
                                {{ $day['label'] }}
                            </div>
                        @endforeach
                    </div>

                    {{-- Body --}}
                    <div class="gantt-timeline-body" @scroll.self="syncScroll">
                        @foreach($tasks->whereNull('parent_id') as $task)
                            @include('modules.project::gantt.components.timeline', ['task' => $task])
                        @endforeach

                        {{-- Today line --}}
                        @php $todayOffset = $view_start_date->diffInDays(now(), false); @endphp
                        @if($todayOffset >= 0 && $todayOffset < count($timeline_days))
                            <div class="gantt-today-line" style="left: {{ $todayOffset * 40 }}px;"></div>
                        @endif
                    </div>

                    {{-- Dependencies SVG layer --}}
                    <svg class="gantt-dependencies" id="gantt-dependencies">
                        {{-- Arrows rendered via JS --}}
                    </svg>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="{{ asset('vendor/polirium/modules/project/js/gantt-chart.js') }}"></script>
        <script>
            @this.call('updateTaskPosition', $event.detail);
        </script>
    @endpush
</x-ui.layouts::app>
```

**3.2 timeline.blade.php** (Timeline row component):
```blade
@php
    $position = $this->getTaskPosition($task);
    $rowHeight = 40;
    $topPosition = $task->parent_id ? ($task->parent->sort_order * $rowHeight) : ($task->sort_order * $rowHeight);
@endphp

<div class="gantt-row" style="top: {{ $topPosition }}px;">
    {{-- Task bar }}
    <div class="gantt-task-bar status-{{ $task->status }} priority-{{ $task->priority }}"
         style="left: {{ $position['left'] }}px; width: {{ $position['width'] }}px;"
         x-data="{ dragging: false, resizing: false }"
         @mousedown="startDrag($event, {{ $task->id }})"
         wire:click="editTask({{ $task->id }})">
        {{ $task->name }}
    </div>

    {{-- Subtasks (if expanded) --}}
    @if($task->children->count() > 0 && in_array($task->id, $expanded_tasks))
        @foreach($task->children as $child)
            @include('modules.project::gantt.components.timeline', ['task' => $child])
        @endforeach
    @endif
</div>
```

**3.3 task-tree.blade.php** (Tree component):
```blade
<div class="task-row @if($editing_task?->id == $task->id) active @endif"
     style="padding-left: {{ $level * 20 }}px;"
     wire:click="editTask({{ $task->id }})">
    @if($task->children->count() > 0)
        <span class="task-toggle" wire:click="toggleTask({{ $task->id }})">
            {!! in_array($task->id, $expanded_tasks) ? tabler_icon('chevron-down') : tabler_icon('chevron-right') !!}
        </span>
    @else
        <span class="task-toggle"></span>
    @endif

    <span>{{ $task->name }}</span>
    <span class="badge bg-{{ $task->status === 'done' ? 'success' : 'secondary' }} ms-auto">
        {{ $task->status }}
    </span>
</div>

@if($task->children->count() > 0 && in_array($task->id, $expanded_tasks))
    @foreach($task->children as $child)
        @include('modules.project::gantt.components.task-tree', ['task' => $child, 'level' => $level + 1])
    @endforeach
@endif
```

### Step 4: Create Alpine.js Component (3h)

**4.1 gantt-chart.js**:
```javascript
// Gantt Chart Alpine Component
function ganttChart() {
    return {
        isDragging: false,
        dragTarget: null,
        dragStartX: 0,
        originalLeft: 0,

        init() {
            // Initialize drag handlers
            this.$nextTick(() => {
                this.renderDependencies();
            });
        },

        /**
         * Start dragging a task
         */
        startDrag(event, taskId) {
            if (event.target.classList.contains('gantt-task-bar')) {
                this.isDragging = true;
                this.dragTarget = event.target;
                this.dragStartX = event.clientX;
                this.originalLeft = parseInt(event.target.style.left);

                document.addEventListener('mousemove', this.onDrag);
                document.addEventListener('mouseup', this.stopDrag);
                event.preventDefault();
            }
        },

        /**
         * On drag move
         */
        onDrag(event) {
            if (!this.isDragging) return;

            const deltaX = event.clientX - this.dragStartX;
            const newLeft = this.originalLeft + deltaX;

            // Snap to grid (day width = 40px)
            const dayWidth = this.getDayWidth();
            const snappedLeft = Math.round(newLeft / dayWidth) * dayWidth;

            this.dragTarget.style.left = snappedLeft + 'px';
            this.renderDependencies(); // Re-render arrows
        },

        /**
         * Stop dragging
         */
        stopDrag(event) {
            if (!this.isDragging) return;

            const taskId = this.dragTarget.dataset.taskId;
            const newLeft = parseInt(this.dragTarget.style.left);
            const dayWidth = this.getDayWidth();
            const daysOffset = Math.round(newLeft / dayWidth);

            // Calculate new dates and dispatch to Livewire
            const startDate = new Date(this.$wire.view_start_date);
            startDate.setDate(startDate.getDate() + daysOffset);

            const taskDuration = parseInt(this.dragTarget.style.width) / dayWidth;
            const endDate = new Date(startDate);
            endDate.setDate(endDate.getDate() + taskDuration - 1);

            this.$dispatch('task-position-updated', {
                id: taskId,
                start_date: startDate.toISOString(),
                end_date: endDate.toISOString(),
            });

            this.isDragging = false;
            this.dragTarget = null;

            document.removeEventListener('mousemove', this.onDrag);
            document.removeEventListener('mouseup', this.stopDrag);
        },

        /**
         * Get day width based on zoom
         */
        getDayWidth() {
            const zoom = this.$wire.zoom;
            return { day: 40, week: 20, month: 15 }[zoom] || 20;
        },

        /**
         * Render dependency arrows
         */
        renderDependencies() {
            const svg = document.getElementById('gantt-dependencies');
            if (!svg) return;

            // Clear existing arrows
            svg.innerHTML = '';

            const dependencies = this.$wire.dependencies || [];
            const tasks = this.$wire.tasks || [];

            dependencies.forEach(dep => {
                const predecessor = tasks.find(t => t.id === dep.predecessor_id);
                const successor = tasks.find(t => t.id === dep.successor_id);

                if (predecessor && successor) {
                    const path = this.drawArrow(predecessor, successor, dep.type);
                    if (path) svg.appendChild(path);
                }
            });
        },

        /**
         * Draw SVG arrow between tasks
         */
        drawArrow(fromTask, toTask, type) {
            // Get task bar positions
            const fromEl = document.querySelector(`[data-task-id="${fromTask.id}"]`);
            const toEl = document.querySelector(`[data-task-id="${toTask.id}"]`);

            if (!fromEl || !toEl) return null;

            const fromRect = fromEl.getBoundingClientRect();
            const toRect = toEl.getBoundingClientRect();
            const containerRect = document.querySelector('.gantt-timeline-body').getBoundingClientRect();

            // Calculate positions relative to container
            const x1 = fromRect.right - containerRect.left;
            const y1 = fromRect.top + fromRect.height / 2 - containerRect.top;
            const x2 = toRect.left - containerRect.left;
            const y2 = toRect.top + toRect.height / 2 - containerRect.top;

            // Create SVG path
            const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            const d = this.calculateArrowPath(x1, y1, x2, y2, type);
            path.setAttribute('d', d);
            path.setAttribute('class', 'gantt-arrow');

            // Add arrow head
            const marker = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            marker.setAttribute('cx', x2);
            marker.setAttribute('cy', y2);
            marker.setAttribute('r', '3');
            path.appendChild(marker);

            return path;
        },

        /**
         * Calculate arrow path based on dependency type
         */
        calculateArrowPath(x1, y1, x2, y2, type) {
            const midX = (x1 + x2) / 2;

            switch (type) {
                case 'finish_to_start':
                    return `M ${x1} ${y1} L ${midX} ${y1} L ${midX} ${y2} L ${x2} ${y2}`;
                case 'start_to_start':
                    return `M ${x1} ${y1} L ${x1 - 20} ${y1} L ${x2 - 20} ${y2} L ${x2} ${y2}`;
                case 'finish_to_finish':
                    return `M ${x1} ${y1} L ${x1 + 20} ${y1} L ${x2 + 20} ${y2} L ${x2} ${y2}`;
                default:
                    return `M ${x1} ${y1} L ${x2} ${y2}`;
            }
        },

        /**
         * Sync scroll between tree and timeline
         */
        syncScroll(event) {
            const tree = document.querySelector('.gantt-task-tree');
            if (tree) {
                tree.scrollTop = event.target.scrollTop;
            }
        },
    };
}

// Make available globally
window.ganttChart = ganttChart;
```

### Step 5: Add Routes & Test (1h)

**5.1 Add Route**:
```php
// routes/web.php
Route::get('gantt', 'ProjectController@gantt')
    ->name('gantt')
    ->middleware('can:projects.index');
```

**5.2 Test Checklist**:
- [ ] Gantt loads with tasks
- [ ] Task bars display at correct positions
- [ ] Drag task updates backend
- [ ] Dependency arrows render
- [ ] Zoom levels work
- [ ] Tree expands/collapses
- [ ] Filters work
- [ ] Scroll syncs between panels
- [ ] Today line displays
- [ ] Mobile responsive

---

## Todo List

### Setup
- [ ] Create gantt-chart.scss in polirium/styles
- [ ] Add to app.scss and build
- [ ] Create Livewire component
- [ ] Create Alpine.js component

### Backend
- [ ] Implement ProjectGanttComponent
- [ ] Implement GanttDataTrait
- [ ] Implement GanttSyncTrait
- [ ] Add API routes

### Frontend
- [ ] Create index.blade.php
- [ ] Create timeline.blade.php
- [ ] Create task-tree.blade.php
- [ ] Create gantt-chart.js

### Features
- [ ] Display task timeline
- [ ] Color tasks by status
- [ ] Draw dependency arrows
- [ ] Drag to reschedule
- [ ] Zoom controls
- [ ] Task tree expand/collapse
- [ ] Filter by project/status

### Testing
- [ ] Test drag-drop
- [ ] Test dependency rendering
- [ ] Test zoom levels
- [ ] Test mobile view
- [ ] Performance test (200 tasks)

---

## Success Criteria

### Display
- [ ] Timeline renders correctly
- [ ] Task bars positioned by date
- [ ] Dependency arrows visible
- [ ] Today line displayed
- [ ] Weekend highlighted

### Interactions
- [ ] Drag updates backend
- [ ] Zoom changes scale
- [ ] Tree expands/collapses
- [ ] Click opens edit modal

### Performance
- [ ] Loads in <2s with 200 tasks
- [ ] Smooth drag-drop (60fps)

---

## Risk Assessment

| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| Complex drag-drop logic | High | Medium | Start with basic drag, add features iteratively |
| Dependency arrow rendering | Medium | High | Use simple SVG paths first |
| Performance with 500+ tasks | High | Medium | Implement virtual scrolling later |
| Mobile responsiveness | Medium | High | Add horizontal scroll, test on devices |

---

## Security Considerations

1. **Authorization**: Check permissions before updates
2. **CSRF**: Use Livewire wire:click (auto-includes token)
3. **SQL Injection**: Use Eloquent ORM
4. **XSS**: Escape task names in Blade

---

## Next Steps

**Phase 05** (Progress & Notifications):
1. Calculate progress percentages
2. Detect overdue tasks
3. Send notifications
4. Add dashboard widgets

**Phase 06** (Permissions & Menu):
1. Complete permissions
2. Finalize translations
3. Write documentation

---

## Unresolved Questions

1. Should we add task resizing (drag edge)?
2. How to handle overlapping tasks visually?
3. Should we support multiple projects on one Gantt?
4. Export to PDF/PNG - use library or build custom?

---

**Last Updated**: 2026-01-17
**Next Review**: After basic timeline rendering
**Blocking**: Phase 01, Phase 02
