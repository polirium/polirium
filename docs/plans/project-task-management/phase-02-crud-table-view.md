# Phase 02: Basic CRUD - Table View

**Date**: 2026-01-17
**Priority**: P1 (Critical Path)
**Estimated Effort**: 10 hours
**Status**: pending

---

## Context Links

**Dependencies**: Phase 01 (Database & Models)
**Related Phases**: Phase 03 (Kanban), Phase 04 (Gantt)
**Research**: `scout/scout-01-codebase-structure.md` (Section 3: UI/View Patterns)

---

## Overview

Build complete CRUD functionality with PowerGrid table view for Projects and Tasks, following Polirium's established patterns from vendor/customer modules.

**Key Objectives**:
1. Create PowerGrid table components (ProjectTable, TaskTable)
2. Build filter sidebars (ProjectFilter, TaskFilter)
3. Implement create/edit modals (ModalCreateProject, ModalCreateTask)
4. Configure permissions and menu items
5. Add translation keys (vi/en)

---

## Key Insights from Research

### PowerGrid Pattern
- **Source**: scout-01-codebase-structure.md, Section 3.2
- **Base Class**: Extend `Polirium\Core\Support\Http\Livewire\Tables\BaseTable`
- **Methods**: `setUp()`, `datasource()`, `fields()`, `columns()`, `actions()`
- **Export**: Include exportable (XLS, CSV)

### Filter Sidebar Pattern
- **Source**: scout-01-codebase-structure.md, Section 3.1
- **Component**: Separate Livewire component for filters
- **Events**: Dispatch `datatable-{table}-filter` events
- **Debounce**: Use `wire:model.live.debounce.300ms` for search inputs

### Modal Pattern
- **Source**: scout-01-codebase-structure.md, Section 3.4
- **Events**: Listen for `show-modal-{entity}` event
- **Validation**: Use `#[Rule]` attributes or `rules()` method
- **Callbacks**: Refresh tables after save with `pg:eventRefresh-table-{table}`

---

## Requirements

### Functional Requirements

**FR-02.1**: Project Table View
- Display columns: ID, Code, Name, Status, Priority, Progress, Dates, Actions
- Sortable columns (all except actions)
- Searchable columns: Code, Name
- Export to XLS/CSV
- Bulk actions: Delete, Change Status

**FR-02.2**: Task Table View
- Display columns: ID, Code, Name, Project, Status, Priority, Assignee, Progress, Due Date, Actions
- Hierarchical indentation for subtasks
- Sortable columns
- Searchable columns: Code, Name, Assignee
- Export to XLS/CSV
- Bulk actions: Delete, Assign, Change Status

**FR-02.3**: Project Filter Sidebar
- Search by name/code
- Filter by status
- Filter by priority
- Filter by date range (start/end)
- Active filter indicator
- Clear filter button

**FR-02.4**: Task Filter Sidebar
- Search by name/code
- Filter by project
- Filter by status
- Filter by priority
- Filter by assignee
- Filter by date range
- Active filter indicator
- Clear filter button

**FR-02.5**: Create/Edit Modals
- Project modal: Name, description, client, status, priority, dates, budget
- Task modal: Name, description, project, parent task, assignee, status, priority, dates, hours
- Validation rules
- Success notifications

### Non-Functional Requirements

**NFR-02.1**: Follow Polirium button standards (x-ui::button)
**NFR-02.2**: Use Tabler icons (tabler_icon helper)
**NFR-02.3**: Translation keys for all text
**NFR-02.4**: Permission-based access control
**NFR-02.5**: Responsive layout (sidebar + table)

---

## Architecture

### Component Structure

```
Platform\Modules\Project\Http\Livewire\
├── Project\
│   ├── Datatable\
│   │   └── ProjectTable.php
│   ├── FilterSidebarComponent.php
│   └── Modal\
│       └── ModalCreateProjectComponent.php
└── Task\
    ├── Datatable\
    │   └── TaskTable.php
    ├── FilterSidebarComponent.php
    └── Modal\
        └── ModalCreateTaskComponent.php
```

### View Structure

```
resources/views/
├── project/
│   ├── index/
│   │   ├── index.blade.php
│   │   ├── filter-sidebar.blade.php
│   │   └── datatable/
│   │       ├── header.blade.php
│   │       └── footer.blade.php
│   └── modal/
│       └── modal-create-project.blade.php
└── task/
    ├── index/
    │   ├── index.blade.php
    │   ├── filter-sidebar.blade.php
    │   └── datatable/
    │       ├── header.blade.php
    │       └── footer.blade.php
    └── modal/
        └── modal-create-task.blade.php
```

---

## Related Code Files

### Reference Files (Study These)

**Vendor Module**:
- `/Users/vingamagic/Developer/php/polirium/platform/modules/vendor/src/Http/Livewire/VendorGroup/Datatable/VendorGroupTable.php`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/vendor/src/Http/Livewire/VendorGroup/FilterSidebarComponent.php`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/vendor/src/Http/Livewire/Index/Modal/ModalCreateVendorGroupComponent.php`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/vendor/resources/views/vendor-group/index.blade.php`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/vendor/resources/views/vendor-group/filter-sidebar.blade.php`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/vendor/resources/views/index/modal/modal-create-vendor-group.blade.php`

**Core Base**:
- `/Users/vingamagic/Developer/php/polirium/platform/core/support/src/Http/Livewire/Tables/BaseTable.php`

### Files to Create

**Livewire Components**:
- `/Users/vingamagic/Developer/php/polirium/platform/modules/project/src/Http/Livewire/Project/Datatable/ProjectTable.php`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/project/src/Http/Livewire/Project/FilterSidebarComponent.php`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/project/src/Http/Livewire/Project/Modal/ModalCreateProjectComponent.php`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/project/src/Http/Livewire/Task/Datatable/TaskTable.php`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/project/src/Http/Livewire/Task/FilterSidebarComponent.php`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/project/src/Http/Livewire/Task/Modal/ModalCreateTaskComponent.php`

**Blade Views**:
- `/Users/vingamagic/Developer/php/polirium/platform/modules/project/resources/views/project/index/index.blade.php`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/project/resources/views/project/index/filter-sidebar.blade.php`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/project/resources/views/project/modal/modal-create-project.blade.php`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/project/resources/views/task/index/index.blade.php`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/project/resources/views/task/index/filter-sidebar.blade.php`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/project/resources/views/task/modal/modal-create-task.blade.php`

**Config Files**:
- `/Users/vingamagic/Developer/php/polirium/platform/modules/project/config/livewire.php`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/project/config/permissions.php`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/project/config/menu.php`

**Translation Files**:
- `/Users/vingamagic/Developer/php/polirium/platform/modules/project/resources/lang/en/project.php`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/project/resources/lang/vi/project.php`

**Routes**:
- `/Users/vingamagic/Developer/php/polirium/platform/modules/project/routes/web.php`

---

## Implementation Steps

### Step 1: Create Config Files (1h)

**1.1 Livewire Config** (`config/livewire.php`):
```php
<?php

use Polirium\Modules\Project\Http\Livewire\Project\Datatable\ProjectTable;
use Polirium\Modules\Project\Http\Livewire\Project\FilterSidebarComponent as ProjectFilterSidebar;
use Polirium\Modules\Project\Http\Livewire\Project\Modal\ModalCreateProjectComponent;
use Polirium\Modules\Project\Http\Livewire\Task\Datatable\TaskTable;
use Polirium\Modules\Project\Http\Livewire\Task\FilterSidebarComponent as TaskFilterSidebar;
use Polirium\Modules\Project\Http\Livewire\Task\Modal\ModalCreateTaskComponent;

return [
    'project-table' => [
        'class' => ProjectTable::class,
        'alias' => 'modules/project::project-table',
        'description' => 'Project Table',
    ],
    'project-filter-sidebar' => [
        'class' => ProjectFilterSidebar::class,
        'alias' => 'modules/project::project-filter-sidebar',
        'description' => 'Project Filter Sidebar',
    ],
    'modal-create-project' => [
        'class' => ModalCreateProjectComponent::class,
        'alias' => 'modules/project::modal-create-project',
        'description' => 'Modal Create Project',
    ],
    'task-table' => [
        'class' => TaskTable::class,
        'alias' => 'modules/project::task-table',
        'description' => 'Task Table',
    ],
    'task-filter-sidebar' => [
        'class' => TaskFilterSidebar::class,
        'alias' => 'modules/project::task-filter-sidebar',
        'description' => 'Task Filter Sidebar',
    ],
    'modal-create-task' => [
        'class' => ModalCreateTaskComponent::class,
        'alias' => 'modules/project::modal-create-task',
        'description' => 'Modal Create Task',
    ],
];
```

**1.2 Permissions** (`config/permissions.php`):
```php
<?php

return [
    [
        'name' => 'Dự án',
        'flag' => 'projects',
    ],
    [
        'name' => 'Xem danh sách dự án',
        'flag' => 'projects.index',
        'parent_flag' => 'projects',
    ],
    [
        'name' => 'Tạo dự án',
        'flag' => 'projects.create',
        'parent_flag' => 'projects',
    ],
    [
        'name' => 'Sửa dự án',
        'flag' => 'projects.edit',
        'parent_flag' => 'projects',
    ],
    [
        'name' => 'Xóa dự án',
        'flag' => 'projects.delete',
        'parent_flag' => 'projects',
    ],
    [
        'name' => 'Công việc',
        'flag' => 'tasks',
    ],
    [
        'name' => 'Xem danh sách công việc',
        'flag' => 'tasks.index',
        'parent_flag' => 'tasks',
    ],
    [
        'name' => 'Tạo công việc',
        'flag' => 'tasks.create',
        'parent_flag' => 'tasks',
    ],
    [
        'name' => 'Sửa công việc',
        'flag' => 'tasks.edit',
        'parent_flag' => 'tasks',
    ],
    [
        'name' => 'Xóa công việc',
        'flag' => 'tasks.delete',
        'parent_flag' => 'tasks',
    ],
];
```

**1.3 Menu** (`config/menu.php`):
```php
<?php

return [
    [
        'id' => 'module_project',
        'name' => trans('modules/project::project.name'),
        'route' => null,
        'icon' => 'chart-gantt',
        'sort' => 30,
    ],
    [
        'id' => 'module_project_index',
        'name' => trans('modules/project::project.name'),
        'route' => 'projects.index',
        'parent' => 'module_project',
        'icon' => 'folder',
        'sort' => 0,
        'permission' => 'projects.index',
    ],
    [
        'id' => 'module_task_index',
        'name' => trans('modules/project::task.name'),
        'route' => 'tasks.index',
        'parent' => 'module_project',
        'icon' => 'checklist',
        'sort' => 1,
        'permission' => 'tasks.index',
    ],
];
```

### Step 2: Create Routes (0.5h)

**2.1 Web Routes** (`routes/web.php`):
```php
<?php

use Illuminate\Support\Facades\Route;

Route::prefix(admin_prefix())
    ->middleware(['web', 'auth'])
    ->namespace('Polirium\Modules\Project\Http\Controllers')
    ->group(function () {
        Route::prefix('projects')->name('projects.')->group(function () {
            Route::get('', 'ProjectController@index')
                ->name('index')
                ->middleware('can:projects.index');
        });

        Route::prefix('tasks')->name('tasks.')->group(function () {
            Route::get('', 'TaskController@index')
                ->name('index')
                ->middleware('can:tasks.index');
        });
    });
```

**2.2 Create Controllers**:
```bash
touch platform/modules/project/src/Http/Controllers/ProjectController.php
touch platform/modules/project/src/Http/Controllers/TaskController.php
```

```php
<?php
namespace Polirium\Modules\Project\Http\Controllers;

class ProjectController extends Controller
{
    public function index()
    {
        return view('modules/project::project/index/index');
    }
}

class TaskController extends Controller
{
    public function index()
    {
        return view('modules/project::task/index/index');
    }
}
```

### Step 3: Create Project Table Component (2h)

**3.1 ProjectTable.php**:
```php
<?php

namespace Polirium\Modules\Project\Http\Livewire\Project\Datatable;

use Illuminate\Database\Eloquent\Builder;
use Polirium\Core\Support\Http\Livewire\Tables\BaseTable;
use Polirium\Modules\Project\Http\Models\Project;
use Polirium\Datatable\Button;
use Polirium\Datatable\Column;
use Polirium\Datatable\Facades\PowerGrid;
use Polirium\Datatable\Components\SetUp\Exportable;

final class ProjectTable extends BaseTable
{
    public string $tableName = 'table-projects';

    public function setUp(): array
    {
        $this->showCheckBox();

        return [
            PowerGrid::exportable('file')->striped()->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV),

            PowerGrid::header()
                ->showSearchInput()
                ->showToggleColumns(),

            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return Project::query()
            ->with(['branch', 'createdBy'])
            ->orderByDesc('id');
    }

    public function relationSearch(): array
    {
        return [
            'branch' => ['name'],
            'createdBy' => ['name', 'email'],
        ];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields();
    }

    public function columns(): array
    {
        return [
            Column::make(trans('core/base::general.id'), 'id')
                ->sortable()
                ->searchable(),

            Column::make(trans('modules/project::project.code'), 'code')
                ->sortable()
                ->searchable(),

            Column::make(trans('modules/project::project.name'), 'name')
                ->sortable()
                ->searchable()
                ->editOnClick(),

            Column::make(trans('modules/project::project.status'), 'status')
                ->sortable()
                ->template('modules/project::project/datatable.columns.status'),

            Column::make(trans('modules/project::project.priority'), 'priority')
                ->sortable()
                ->template('modules/project::project/datatable.columns.priority'),

            Column::make(trans('modules/project::project.progress'), 'progress_percentage')
                ->sortable()
                ->template('modules/project::project/datatable.columns.progress'),

            Column::make(trans('modules/project::project.planned_end_date'), 'planned_end_date')
                ->sortable()
                ->template('modules/project::project/datatable.columns.date'),

            Column::action(trans('core/base::general.action')),
        ];
    }

    public function actions(Project $row): array
    {
        return [
            Button::add('edit-project')
                ->slot(tabler_icon('pencil', ['class' => 'icon']))
                ->id()
                ->class('btn btn-primary btn-icon btn-sm me-1')
                ->dispatch('show-modal-create-project', ['id' => $row->id])
                ->tooltip(trans('core/base::general.edit')),

            Button::add('delete-project')
                ->slot(tabler_icon('trash', ['class' => 'icon']))
                ->id()
                ->class('btn btn-danger btn-icon btn-sm')
                ->dispatch('delete-project', ['id' => $row->id])
                ->confirm(trans('core/base::general.confirm_delete'))
                ->tooltip(trans('core/base::general.delete')),
        ];
    }

    public function filters(): array
    {
        return [
            // Add filters if needed
        ];
    }
}
```

### Step 4: Create Filter Sidebar (1.5h)

**4.1 ProjectFilterSidebarComponent.php**:
```php
<?php

namespace Polirium\Modules\Project\Http\Livewire\Project;

use Livewire\Component;

class FilterSidebarComponent extends Component
{
    public $search = [
        'name' => '',
        'status' => '',
        'priority' => '',
    ];

    public function updatedSearch($value, $key)
    {
        $this->dispatch("datatable-project-filter", $value, $key);
    }

    public function clearFilter()
    {
        $this->search = ['name' => '', 'status' => '', 'priority' => ''];
        $this->dispatch("datatable-project-filter", '', 'all');
    }

    public function render()
    {
        return view('modules/project::project/filter-sidebar');
    }
}
```

**4.2 Filter Sidebar View**:
```blade
<div>
    {{-- Filter Panel --}}
    <x-ui::card>
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="d-flex align-items-center gap-2">
                {!! tabler_icon('filter', ['class' => 'icon text-primary']) !!}
                <span class="fw-semibold">{{ __('core/base::general.filter') }}</span>
            </div>
        </div>

        {{-- Search by name/code --}}
        <div class="mb-3">
            <label class="form-label small text-muted">
                {{ __('modules/project::project.search_name') }}
            </label>
            <div class="input-icon">
                <span class="input-icon-addon">
                    {!! tabler_icon('search', ['class' => 'icon']) !!}
                </span>
                <input
                    type="text"
                    class="form-control"
                    wire:model.live.debounce.300ms="search.name"
                    placeholder="{{ __('modules/project::project.search_placeholder') }}"
                >
            </div>
        </div>

        {{-- Filter by status --}}
        <div class="mb-3">
            <label class="form-label small text-muted">
                {{ __('modules/project::project.status') }}
            </label>
            <select class="form-select" wire:model.live="search.status">
                <option value="">{{ __('core/base::general.all') }}</option>
                <option value="planning">{{ __('modules/project::status.planning') }}</option>
                <option value="active">{{ __('modules/project::status.active') }}</option>
                <option value="on_hold">{{ __('modules/project::status.on_hold') }}</option>
                <option value="completed">{{ __('modules/project::status.completed') }}</option>
                <option value="cancelled">{{ __('modules/project::status.cancelled') }}</option>
            </select>
        </div>

        {{-- Filter by priority --}}
        <div class="mb-3">
            <label class="form-label small text-muted">
                {{ __('modules/project::project.priority') }}
            </label>
            <select class="form-select" wire:model.live="search.priority">
                <option value="">{{ __('core/base::general.all') }}</option>
                <option value="low">{{ __('modules/project::priority.low') }}</option>
                <option value="medium">{{ __('modules/project::priority.medium') }}</option>
                <option value="high">{{ __('modules/project::priority.high') }}</option>
                <option value="urgent">{{ __('modules/project::priority.urgent') }}</option>
            </select>
        </div>

        {{-- Active filter indicator --}}
        @if (!empty($search['name']) || !empty($search['status']) || !empty($search['priority']))
            <div class="p-2 bg-primary-lt rounded d-flex align-items-center justify-content-between">
                <span class="small text-primary">
                    {!! tabler_icon('filter-check', ['class' => 'icon icon-sm me-1']) !!}
                    {{ __('core/base::general.filter_active') }}
                </span>
                <button
                    class="btn btn-sm btn-ghost-danger btn-icon"
                    wire:click="clearFilter"
                    title="{{ __('core/base::general.clear_filter') }}"
                >
                    {!! tabler_icon('x', ['class' => 'icon icon-sm']) !!}
                </button>
            </div>
        @endif
    </x-ui::card>

    {{-- Quick Actions --}}
    <x-ui::card class="mt-3">
        <div class="d-flex align-items-center gap-2 mb-2">
            {!! tabler_icon('bolt', ['class' => 'icon text-warning']) !!}
            <span class="fw-semibold">{{ __('core/base::general.quick_actions') }}</span>
        </div>

        <div class="d-grid gap-2">
            <button
                class="btn btn-outline-primary d-flex align-items-center justify-content-start gap-2"
                wire:click="$dispatch('show-modal-create-project')"
            >
                {!! tabler_icon('plus', ['class' => 'icon']) !!}
                {{ __('modules/project::project.create') }}
            </button>
        </div>
    </x-ui::card>
</div>
```

### Step 5: Create Modal Component (2h)

**5.1 ModalCreateProjectComponent.php**:
```php
<?php

namespace Polirium\Modules\Project\Http\Livewire\Project\Modal;

use Livewire\Component;
use Livewire\Attributes\On;
use Polirium\Modules\Project\Http\Models\Project;
use Polirium\Core\Base\Http\Models\Branch\Branch;

class ModalCreateProjectComponent extends Component
{
    public ?int $project_id = null;

    public array $input = [
        'name' => '',
        'description' => '',
        'status' => 'planning',
        'priority' => 'medium',
        'planned_start_date' => null,
        'planned_end_date' => null,
        'budget' => 0,
        'branch_id' => null,
    ];

    protected function rules()
    {
        $table = (new Project)->getTable();

        return [
            'input.name' => "required|string|max:191|unique:{$table},name,{$this->project_id},id",
            'input.description' => 'nullable|string|max:1000',
            'input.status' => 'required|in:planning,active,on_hold,completed,cancelled',
            'input.priority' => 'required|in:low,medium,high,urgent',
            'input.planned_start_date' => 'nullable|date',
            'input.planned_end_date' => 'nullable|date|after:input.planned_start_date',
            'input.budget' => 'nullable|numeric|min:0',
            'input.branch_id' => 'nullable|exists:branches,id',
        ];
    }

    public function mount()
    {
        $this->resetInput();
    }

    public function render()
    {
        $branches = Branch::all();

        return view('modules/project::project/modal/modal-create-project', compact('branches'));
    }

    public function resetInput()
    {
        $this->input = [
            'name' => '',
            'description' => '',
            'status' => 'planning',
            'priority' => 'medium',
            'planned_start_date' => null,
            'planned_end_date' => null,
            'budget' => 0,
            'branch_id' => auth()->user()->branch_id ?? null,
        ];
    }

    #[On('show-modal-create-project')]
    public function showModal(?int $id = null)
    {
        $this->project_id = $id;
        $this->resetInput();

        if ($id) {
            $project = Project::findOrFail($id);
            $this->input = $project->toArray();
        }

        $this->dispatch("modal", "modal-create-project");
    }

    public function save()
    {
        $this->validate();

        $this->input['created_by'] = auth()->id();
        $this->input['code'] = $this->generateProjectCode();

        if ($this->project_id) {
            $project = Project::find($this->project_id);
            $project->update($this->input);
        } else {
            Project::create($this->input);
        }

        $this->resetInput();
        $this->dispatch("close-modal-create-project");
        $this->dispatch('pg:eventRefresh-table-projects');
    }

    private function generateProjectCode(): string
    {
        $prefix = 'PRJ';
        $latest = Project::orderByDesc('id')->first();
        $number = $latest ? (int) substr($latest->code, -4) + 1 : 1;
        return $prefix . str_pad($number, 4, '0', STR_PAD_LEFT);
    }
}
```

**5.2 Modal View**:
```blade
<div>
    <form wire:submit.prevent="save">
        <x-ui::modal id="modal-create-project"
                    :header="trans('modules/project::project.' . ($project_id ? 'edit' : 'create'))">
            <x-ui::errors/>

            <div class="row g-3">
                {{-- Name --}}
                <div class="col-12">
                    <x-ui.form.input
                        wire:model="input.name"
                        :label="trans('modules/project::project.name')"
                        :placeholder="trans('modules/project::project.enter_name')"
                        icon="folder"
                        required
                    />
                </div>

                {{-- Description --}}
                <div class="col-12">
                    <x-ui.form.textarea
                        wire:model="input.description"
                        :label="trans('core/base::general.description')"
                        :placeholder="trans('modules/project::project.enter_description')"
                        rows="3"
                    />
                </div>

                {{-- Status & Priority --}}
                <div class="col-md-6">
                    <label class="form-label">{{ trans('modules/project::project.status') }}</label>
                    <select class="form-select" wire:model="input.status" required>
                        <option value="planning">{{ trans('modules/project::status.planning') }}</option>
                        <option value="active">{{ trans('modules/project::status.active') }}</option>
                        <option value="on_hold">{{ trans('modules/project::status.on_hold') }}</option>
                        <option value="completed">{{ trans('modules/project::status.completed') }}</option>
                        <option value="cancelled">{{ trans('modules/project::status.cancelled') }}</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">{{ trans('modules/project::project.priority') }}</label>
                    <select class="form-select" wire:model="input.priority" required>
                        <option value="low">{{ trans('modules/project::priority.low') }}</option>
                        <option value="medium">{{ trans('modules/project::priority.medium') }}</option>
                        <option value="high">{{ trans('modules/project::priority.high') }}</option>
                        <option value="urgent">{{ trans('modules/project::priority.urgent') }}</option>
                    </select>
                </div>

                {{-- Dates --}}
                <div class="col-md-6">
                    <x-ui.form.input
                        type="date"
                        wire:model="input.planned_start_date"
                        :label="trans('modules/project::project.planned_start_date')"
                    />
                </div>

                <div class="col-md-6">
                    <x-ui.form.input
                        type="date"
                        wire:model="input.planned_end_date"
                        :label="trans('modules/project::project.planned_end_date')"
                    />
                </div>

                {{-- Budget --}}
                <div class="col-md-6">
                    <x-ui.form.input
                        type="number"
                        step="0.01"
                        wire:model="input.budget"
                        :label="trans('modules/project::project.budget')"
                        icon="currency-dollar"
                    />
                </div>

                {{-- Branch --}}
                <div class="col-md-6">
                    <label class="form-label">{{ trans('core/base::general.branch') }}</label>
                    <select class="form-select" wire:model="input.branch_id">
                        <option value="">{{ trans('core/base::general.select_branch') }}</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <x-slot:footer>
                <x-ui::button color="secondary" :ghost="true" type="button"
                             data-bs-dismiss="modal" icon="x">
                    {{ trans('core/base::general.cancel') }}
                </x-ui::button>
                <x-ui::button color="primary" type="submit" icon="device-floppy"
                             wire:loading.attr="disabled">
                    {{ trans('core/base::general.save') }}
                </x-ui::button>
            </x-slot:footer>
        </x-ui::modal>
    </form>
</div>

@push('scripts')
<script>
    window.addEventListener('close-modal-create-project', event => {
        const modalEl = document.getElementById('modal-create-project');
        if (modalEl) {
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) {
                modal.hide();
            }
        }
        setTimeout(() => {
            document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
        }, 150);
    });
</script>
@endpush
```

### Step 6: Create Index Page (1h)

**6.1 Project Index View**:
```blade
<x-ui.layouts::app>
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        {{ trans('modules/project::project.name') }}
                    </h2>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <div class="btn-list">
                        <button class="btn btn-primary d-none d-sm-inline-block"
                                wire:click="$dispatch('show-modal-create-project')">
                            {!! tabler_icon('plus', ['class' => 'icon']) !!}
                            {{ trans('modules/project::project.create') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3">
            @livewire('modules/project::project-filter-sidebar')
        </div>
        <div class="col-md-9">
            <x-ui::card>
                @livewire('modules/project::project-table')
            </x-ui::card>
        </div>
    </div>

    @livewire('modules/project::modal-create-project')
</x-ui.layouts::app>
```

### Step 7: Create Translations (1h)

**7.1 Vietnamese** (`resources/lang/vi/project.php`):
```php
<?php

return [
    'name' => 'Dự án',
    'create' => 'Thêm dự án',
    'edit' => 'Sửa dự án',
    'delete' => 'Xóa dự án',
    'enter_name' => 'Nhập tên dự án...',
    'enter_description' => 'Nhập mô tả...',
    'search_name' => 'Tìm theo tên/mã',
    'search_placeholder' => 'Nhập tên hoặc mã dự án...',

    'code' => 'Mã dự án',
    'description' => 'Mô tả',
    'status' => 'Trạng thái',
    'priority' => 'Mức độ ưu tiên',
    'planned_start_date' => 'Ngày bắt đầu',
    'planned_end_date' => 'Ngày kết thúc',
    'budget' => 'Ngân sách',
    'progress' => 'Tiến độ',

    // Task
    'task' => [
        'name' => 'Công việc',
        'create' => 'Thêm công việc',
        'edit' => 'Sửa công việc',
        'delete' => 'Xóa công việc',
        'parent_task' => 'Công việc cha',
        'assigned_to' => 'Người phụ trách',
        'estimated_hours' => 'Giờ ước tính',
        'actual_hours' => 'Giờ thực tế',
    ],

    // Status
    'status' => [
        'planning' => 'Lên kế hoạch',
        'active' => 'Đang thực hiện',
        'on_hold' => 'Tạm dừng',
        'completed' => 'Hoàn thành',
        'cancelled' => 'Hủy bỏ',
        'backlog' => 'Chưa lên kế hoạch',
        'todo' => 'Cần làm',
        'in_progress' => 'Đang thực hiện',
        'review' => 'Đang xem xét',
        'done' => 'Hoàn thành',
    ],

    // Priority
    'priority' => [
        'low' => 'Thấp',
        'medium' => 'Trung bình',
        'high' => 'Cao',
        'urgent' => 'Khẩn cấp',
    ],
];
```

**7.2 English** (`resources/lang/en/project.php`):
```php
<?php

return [
    'name' => 'Projects',
    'create' => 'Add Project',
    'edit' => 'Edit Project',
    'delete' => 'Delete Project',
    'enter_name' => 'Enter project name...',
    'enter_description' => 'Enter description...',
    'search_name' => 'Search by name/code',
    'search_placeholder' => 'Enter project name or code...',

    'code' => 'Project Code',
    'description' => 'Description',
    'status' => 'Status',
    'priority' => 'Priority',
    'planned_start_date' => 'Start Date',
    'planned_end_date' => 'End Date',
    'budget' => 'Budget',
    'progress' => 'Progress',

    // Task
    'task' => [
        'name' => 'Tasks',
        'create' => 'Add Task',
        'edit' => 'Edit Task',
        'delete' => 'Delete Task',
        'parent_task' => 'Parent Task',
        'assigned_to' => 'Assigned To',
        'estimated_hours' => 'Estimated Hours',
        'actual_hours' => 'Actual Hours',
    ],

    // Status
    'status' => [
        'planning' => 'Planning',
        'active' => 'Active',
        'on_hold' => 'On Hold',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'backlog' => 'Backlog',
        'todo' => 'To Do',
        'in_progress' => 'In Progress',
        'review' => 'Review',
        'done' => 'Done',
    ],

    // Priority
    'priority' => [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'urgent' => 'Urgent',
    ],
];
```

### Step 8: Test & Verify (1h)

**8.1 Test Flow**:
1. Clear config cache: `php artisan config:clear`
2. Visit `/admin/projects`
3. Test filter sidebar
4. Click "Add Project" button
5. Fill form and submit
6. Verify project appears in table
7. Test edit modal
8. Test delete action
9. Test export to XLS/CSV

**8.2 Repeat for Tasks**:
- Create TaskTable, TaskFilterSidebar, ModalCreateTask
- Follow same pattern as Projects
- Add hierarchical indentation for subtasks

---

## Todo List

### Config & Routes
- [ ] Create livewire.php config
- [ ] Create permissions.php config
- [ ] Create menu.php config
- [ ] Create web.php routes
- [ ] Create controllers (Project, Task)

### Project Components
- [ ] Create ProjectTable.php (PowerGrid)
- [ ] Create ProjectFilterSidebarComponent.php
- [ ] Create ModalCreateProjectComponent.php
- [ ] Create project/index/index.blade.php
- [ ] Create project/filter-sidebar.blade.php
- [ ] Create project/modal/modal-create-project.blade.php

### Task Components
- [ ] Create TaskTable.php (with hierarchical display)
- [ ] Create TaskFilterSidebarComponent.php
- [ ] Create ModalCreateTaskComponent.php
- [ ] Create task/index/index.blade.php
- [ ] Create task/filter-sidebar.blade.php
- [ ] Create task/modal/modal-create-task.blade.php

### Translations
- [ ] Create vi/project.php (Vietnamese)
- [ ] Create en/project.php (English)
- [ ] Add all translation keys
- [ ] Test language switching

### Testing
- [ ] Test project CRUD operations
- [ ] Test task CRUD operations
- [ ] Test filter sidebar events
- [ ] Test modal open/close
- [ ] Test permissions
- [ ] Test export functionality

---

## Success Criteria

### Project CRUD
- [ ] Table displays all projects
- [ ] Create modal opens and saves
- [ ] Edit modal loads existing data
- [ ] Delete action works with confirmation
- [ ] Filter sidebar updates table

### Task CRUD
- [ ] Table displays tasks with hierarchy
- [ ] Create modal allows selecting parent task
- [ ] Edit modal works
- [ ] Delete cascades to subtasks
- [ ] Filter by project works

### UI/UX
- [ ] All buttons use x-ui::button
- [ ] All icons use tabler_icon helper
- [ ] Responsive layout (sidebar collapses)
- [ ] Loading states show during save
- [ ] Error messages display correctly

---

## Risk Assessment

| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| PowerGrid performance issues | Medium | Low | Add pagination, limit columns |
| Modal not closing properly | Low | Medium | Test on multiple browsers |
| Filter events not firing | Medium | Low | Verify event names match |
| Hierarchical task display | Medium | Medium | Use indentation with level indicator |

---

## Security Considerations

1. **Authorization**: All routes protected by permission middleware
2. **Validation**: All inputs validated before saving
3. **Mass Assignment**: Only fillable fields allowed
4. **CSRF**: Laravel CSRF protection enabled

---

## Next Steps

**Phase 03** (Kanban Board):
1. Create Kanban components using Livewire wire:sort
2. Implement drag-drop between status columns
3. Add keyboard navigation
4. Test accessibility

**Phase 04** (Gantt Chart):
1. Install DHTMLX Gantt library
2. Create API endpoints for task data
3. Implement Gantt view
4. Add drag-drop rescheduling

---

## Unresolved Questions

1. How to display hierarchical tasks in table (indentation vs tree view)?
2. Should inline editing be enabled in PowerGrid columns?
3. Bulk actions: delete, change status, reassign - priority order?
4. Task code generation: should it be project-specific (PRJ001-TSK001)?

---

**Last Updated**: 2026-01-17
**Next Review**: After Project CRUD completed
**Blocking**: Phase 01 (Database)
