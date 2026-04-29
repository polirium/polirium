---
title: "Project & Task Management Module"
description: "Module quản lý công việc và dự án với Table, Kanban, Gantt chart views"
status: validated
priority: P1
effort: 52h
branch: main
tags: [project, task, kanban, gantt, module]
created: 2026-01-17
validated: 2026-01-17
---

# Project & Task Management Module Implementation Plan

## Overview

**Module Name**: `project` (platform/modules/project/)

**Objective**: Build a comprehensive project and task management module with hierarchical tasks, multiple views (Table, Kanban, Gantt), progress tracking, and delay management.

**Target Users**: Project managers, team leads, developers, stakeholders

**Key Features**:
1. Hierarchical tasks with unlimited nesting (parent_id pattern)
2. Date management (planned_start, planned_end, actual_start, actual_end)
3. Progress tracking (% completion, delay detection, overdue alerts)
4. Multiple views: Table (PowerGrid), Kanban (wire:sort), Gantt (Custom HTML/CSS)
5. Task dependencies (predecessor/successor relationships)
6. Assignment system (users, teams)
7. Comments, attachments, time logs

**Estimated Effort**: 52 hours (6.5 weeks @ 8h/week) - tăng 4h cho custom Gantt UI

**Tech Stack**:
- Laravel 11 + Livewire 4
- Alpine.js + SortableJS (Kanban)
- Custom Gantt UI (HTML/CSS/Alpine.js - Open Source)
- PowerGrid (Table view)
- Tabler Icons
- Spatie Permissions

---

## Phases

### Phase 01: Database Schema & Models (8h)
**File**: `phase-01-database-models.md`
**Goal**: Create complete database structure, migrations, models with relationships
**Deliverables**: Migrations, Project/Task/Dependency models, factories, seeders

### Phase 02: Basic CRUD - Table View (10h)
**File**: `phase-02-crud-table-view.md`
**Goal**: Implement full CRUD operations with PowerGrid table view
**Deliverables**: Table component, filter sidebar, create/edit modals, permissions

### Phase 03: Kanban Board (8h)
**File**: `phase-03-kanban-board.md`
**Goal**: Build drag-drop Kanban board with Livewire 4 wire:sort
**Deliverables**: Kanban component, drag-drop logic, column management, status updates

### Phase 04: Gantt Chart (14h)
**File**: `phase-04-gantt-chart.md`
**Goal**: Build custom HTML/CSS/Alpine.js Gantt chart UI (open source)
**Deliverables**: Gantt timeline view, task bars, dependency lines, drag-drop rescheduling, zoom levels

### Phase 05: Progress Tracking & Notifications (8h)
**File**: `phase-05-progress-notifications.md`
**Goal**: Implement progress calculation, delay detection, notifications
**Deliverables**: Progress algorithms, delay alerts, notification system, dashboard widgets

### Phase 06: Permissions, Menu & Translations (4h)
**File**: `phase-06-permissions-menu.md`
**Goal**: Complete permission system, menu integration, translations
**Deliverables**: Permission flags, menu items, vi/en translations, documentation

---

## Architecture Summary

### Database Schema

```sql
projects (id, uuid, code, name, description, client_id, status,
          planned_start_date, planned_end_date, actual_start_date, actual_end_date,
          budget, progress_percentage, created_by, branch_id, ...)

tasks (id, uuid, code, project_id, parent_id, name, description,
       status, priority, assigned_to,
       planned_start_date, planned_end_date, actual_start_date, actual_end_date,
       estimated_hours, actual_hours, progress_percentage, sort_order, ...)

task_dependencies (id, predecessor_id, successor_id, dependency_type, lag_days)

task_comments (task_id, user_id, content, created_at)

task_attachments (task_id, file_path, uploaded_by, file_name, file_size)

task_time_logs (task_id, user_id, hours, date, description)
```

### Class Structure

```
Platform\Modules\Project\
├── Http\
│   ├── Models\
│   │   ├── Project.php
│   │   ├── Task.php
│   │   ├── TaskDependency.php
│   │   ├── TaskComment.php
│   │   ├── TaskAttachment.php
│   │   └── TaskTimeLog.php
│   └── Livewire\
│       ├── Project\
│       │   ├── Datatable\ProjectTable.php
│       │   ├── FilterSidebarComponent.php
│       │   ├── Modal\ModalCreateProjectComponent.php
│       │   ├── Kanban\ProjectKanbanComponent.php
│       │   └── Gantt\ProjectGanttComponent.php
│       └── Task\
│           ├── Datatable\TaskTable.php
│           ├── FilterSidebarComponent.php
│           ├── Modal\ModalCreateTaskComponent.php
│           ├── Kanban\TaskKanbanComponent.php
│           └── Gantt\TaskGanttComponent.php
└── Providers\
    └── ProjectServiceProvider.php
```

---

## Key Design Decisions

### 1. Hierarchical Tasks (Single Table Pattern)
- **Decision**: Use single `tasks` table with `parent_id` (not separate Tasks/Subtasks)
- **Rationale**: Flexibility for unlimited nesting, simpler queries, recursive FK
- **Trade-off**: Need careful query optimization for deep hierarchies

### 2. Progress Calculation
- **Primary Method**: Subtask aggregation for parent tasks (weighted average)
- **Secondary Method**: Effort-based for leaf tasks (actual_hours / estimated_hours)
- **Override**: Manual % setting allowed with audit trail

### 3. Kanban Implementation
- **Library**: Livewire 4 native `wire:sort` directive
- **Rationale**: Zero dependencies, built-in support, PHP-friendly
- **Fallback**: Alpine.js Sort Plugin if need advanced features

### 4. Gantt Implementation
- **Decision**: Self-built HTML/CSS/Alpine.js Gantt UI (100% open source)
- **Rationale**: No licensing constraints, full control, follows Polirium patterns
- **Trade-off**: More development effort vs using off-the-shelf library
- **Core Features**: Timeline bars, dependency arrows, drag-drop resizing, zoom (day/week/month)

### 5. Date Fields
- **Planned dates**: `planned_start_date`, `planned_end_date` (DATE columns)
- **Actual dates**: `actual_start_date`, `actual_end_date` (auto-set on status change)
- **Validation**: actual_start ≥ planned_start, actual_end ≥ actual_start

---

## Dependencies

### Technical Dependencies
- Livewire 4.x (for wire:sort)
- Alpine.js 3.x (for Kanban + Gantt interactivity)
- Custom Gantt UI (HTML/CSS/Alpine.js)
- PowerGrid (already in stack)
- Spatie Laravel-Permission (already in stack)

### Module Dependencies
- `platform/core/base` (BaseModel, helpers)
- `platform/core/ui` (x-ui components, Tabler icons)
- `platform/core/support` (PoliriumBaseServiceProvider)

---

## Risk Assessment

| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| Gantt complexity with self-built UI | High | Medium | Start with basic timeline, iterate features, reference open-source implementations |
| Performance with 1000+ tasks | High | Medium | Implement pagination, virtual scrolling, lazy loading |
| Complex dependency cycles | Medium | Medium | Detect cycles before save, prevent circular deps |
| Kanban mobile UX | Medium | High | Test touch events, consider alternative mobile view |
| Real-time sync conflicts | Low | Low | Use Livewire defer, debounce, optimistic UI |

---

## Success Criteria

### Phase 01
- [ ] All migrations run successfully
- [ ] Models have correct relationships (belongsTo, hasMany, belongsToMany)
- [ ] Factory generates realistic test data
- [ ] Recursive parent-child queries work

### Phase 02
- [ ] Table view displays with sort/search/filter
- [ ] Create/edit modals save correctly
- [ ] Permissions restrict access properly
- [ ] Filter sidebar updates table in real-time

### Phase 03
- [ ] Kanban board renders with correct columns
- [ ] Drag-drop moves cards between columns
- [ ] Status updates save to database
- [ ] Keyboard navigation works (accessibility)

### Phase 04
- [ ] Gantt chart displays tasks on timeline
- [ ] Drag-drop rescheduling updates dates
- [ ] Dependency links render correctly
- [ ] Zoom levels (day/week/month) work

### Phase 05
- [ ] Progress % calculates correctly
- [ ] Overdue tasks trigger alerts
- [ ] Notifications send to assignees
- [ ] Dashboard widgets display stats

### Phase 06
- [ ] All translation keys have vi/en values
- [ ] Menu items appear with correct permissions
- [ ] Documentation is complete
- [ ] Module is production-ready

---

## Implementation Order (Critical Path)

1. **Week 1**: Phase 01 (Database) + Phase 06 (Basic permissions)
2. **Week 2**: Phase 02 (CRUD Table) - Projects only
3. **Week 3**: Phase 02 (CRUD Table) - Tasks + Dependencies
4. **Week 4**: Phase 03 (Kanban Board)
5. **Week 5**: Phase 04 (Gantt Chart)
6. **Week 6**: Phase 05 (Progress/Notifications) + Phase 06 (Complete)

---

## Next Steps

1. **Plan validated** - Ready for implementation
2. **Verify Livewire 4 version** in codebase (required for wire:sort)
3. **Create project directory**: `platform/modules/project/`
4. **Begin Phase 01**: Create migrations

---

## Related Files

### Research
- `/docs/plans/project-task-management/research/researcher-01-pm-features.md`
- `/docs/plans/project-task-management/research/researcher-02-ui-libraries.md`
- `/docs/plans/project-task-management/scout/scout-01-codebase-structure.md`

### Phase Plans
- `/docs/plans/project-task-management/phase-01-database-models.md`
- `/docs/plans/project-task-management/phase-02-crud-table-view.md`
- `/docs/plans/project-task-management/phase-03-kanban-board.md`
- `/docs/plans/project-task-management/phase-04-gantt-chart.md`
- `/docs/plans/project-task-management/phase-05-progress-notifications.md`
- `/docs/plans/project-task-management/phase-06-permissions-menu.md`

### Reference Modules (Vendor/Customer patterns)
- `/platform/modules/vendor/composer.json`
- `/platform/modules/vendor/src/Providers/VendorServiceProvider.php`
- `/platform/modules/vendor/database/migrations/*_vendor_create_vendor_table.php`
- `/platform/modules/vendor/src/Http/Model/Vendor.php`
- `/platform/modules/vendor/config/permissions.php`
- `/platform/modules/vendor/config/menu.php`

---

**Last Updated**: 2026-01-17
**Plan Version**: 1.0
**Status**: Ready for Implementation
