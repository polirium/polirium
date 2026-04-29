# Phase 06: Permissions, Menu & Translations

**Date**: 2026-01-17
**Priority**: P2
**Estimated Effort**: 4 hours
**Status**: pending

---

## Context Links

**Dependencies**: Phase 01-05 (All previous phases)
**Related Phases**: Final phase - completes the module
**Research**: `scout/scout-01-codebase-structure.md` (Section 4: Permission & Menu System)

---

## Overview

Complete the Project & Task Management module by finalizing permissions, menu integration, translations, and documentation.

**Key Objectives**:
1. Complete permission configuration with all flags
2. Integrate menu items with proper permissions
3. Finalize translations (vi/en)
4. Add module documentation
5. Prepare for production deployment

---

## Key Insights from Research

### Permission Pattern
- **Source**: scout-01-codebase-structure.md, Section 4.1
- **Structure**: Hierarchical permissions with parent_flag
- **Format**: `['name' => 'Display', 'flag' => 'permission.key', 'parent_flag' => 'parent.key']`
- **Auto-loading**: Permissions auto-discovered from config/permissions.php

### Menu Pattern
- **Source**: scout-01-codebase-structure.md, Section 4.2
- **Structure**: Nested menus with parent/child relationships
- **Format**: `['id', 'name', 'route', 'parent', 'icon', 'sort', 'permission']`
- **Icons**: Use Tabler icon names

### Translation Pattern
- **Format**: `modules/{module}::{file}.{key}`
- **Locations**: `resources/lang/{locale}/{module}.php`
- **Usage**: `{{ __('modules/project::project.name') }}`

---

## Requirements

### Functional Requirements

**FR-06.1**: Permissions
- Project permissions: view, create, edit, delete, manage
- Task permissions: view, create, edit, delete, assign, manage
- Advanced permissions: export, manage_dependencies, view_reports
- Role-based access control integration

**FR-06.2**: Menu Integration
- Top-level menu: "Projects" (icon: chart-gantt)
- Sub-menus: Projects list, Tasks list, Kanban, Gantt
- Sidebar icons: Tabler icons
- Permission-based visibility

**FR-06.3**: Translations
- Complete Vietnamese translations
- Complete English translations
- All UI text translatable
- No hardcoded strings

**FR-06.4**: Documentation
- Module README
- User guide (Vietnamese)
- Developer guide
- API documentation

### Non-Functional Requirements

**NFR-06.1**: All translation keys have vi/en values
**NFR-06.2**: No hardcoded text in Blade views
**NFR-06.3**: Permission middleware on all routes
**NFR-06.4**: Menu items respect permissions

---

## Architecture

### Permission Structure

```
projects (parent)
├── projects.view (view projects)
├── projects.create (create projects)
├── projects.edit (edit projects)
├── projects.delete (delete projects)
└── projects.export (export projects)

tasks (parent)
├── tasks.view (view tasks)
├── tasks.create (create tasks)
├── tasks.edit (edit tasks)
├── tasks.delete (delete tasks)
├── tasks.assign (assign tasks)
└── tasks.export (export tasks)

reports (parent)
├── reports.view (view reports)
└── reports.export (export reports)
```

### Menu Structure

```
Projects (icon: chart-gantt)
├── Projects (icon: folder) → projects.index
├── Tasks (icon: checklist) → tasks.index
├── Kanban (icon: columns) → tasks.kanban
└── Gantt (icon: chart-gantt) → projects.gantt
```

---

## Related Code Files

### Reference Files (Study These)

**Vendor Module Configs**:
- `/Users/vingamagic/Developer/php/polirium/platform/modules/vendor/config/permissions.php`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/vendor/config/menu.php`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/vendor/resources/lang/vi/vendor.php`

### Files to Update

**Config Files**:
- `/Users/vingamagic/Developer/php/polirium/platform/modules/project/config/permissions.php`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/project/config/menu.php`

**Translation Files**:
- `/Users/vingamagic/Developer/php/polirium/platform/modules/project/resources/lang/vi/project.php`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/project/resources/lang/en/project.php`

**Documentation**:
- `/Users/vingamagic/Developer/php/polirium/platform/modules/project/README.md`
- `/Users/vingamagic/Developer/php/polirium/docs/modules/project-management.md`

---

## Implementation Steps

### Step 1: Complete Permissions Configuration (1h)

**1.1 Update config/permissions.php**:
```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Quản lý Dự án
    |--------------------------------------------------------------------------
    */
    [
        'name' => 'Dự án',
        'flag' => 'projects',
    ],
    [
        'name' => 'Xem danh sách dự án',
        'flag' => 'projects.view',
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
        'name' => 'Xuất dữ liệu dự án',
        'flag' => 'projects.export',
        'parent_flag' => 'projects',
    ],
    [
        'name' => 'Quản lý dự án (toàn quyền)',
        'flag' => 'projects.manage',
        'parent_flag' => 'projects',
        'is_default' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Quản lý Công việc
    |--------------------------------------------------------------------------
    */
    [
        'name' => 'Công việc',
        'flag' => 'tasks',
    ],
    [
        'name' => 'Xem danh sách công việc',
        'flag' => 'tasks.view',
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
    [
        'name' => 'Phân công công việc',
        'flag' => 'tasks.assign',
        'parent_flag' => 'tasks',
    ],
    [
        'name' => 'Xuất dữ liệu công việc',
        'flag' => 'tasks.export',
        'parent_flag' => 'tasks',
    ],
    [
        'name' => 'Quản lý công việc (toàn quyền)',
        'flag' => 'tasks.manage',
        'parent_flag' => 'tasks',
        'is_default' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Quản lý Phụ thuộc
    |--------------------------------------------------------------------------
    */
    [
        'name' => 'Quản lý phụ thuộc công việc',
        'flag' => 'tasks.manage_dependencies',
        'parent_flag' => 'tasks',
    ],

    /*
    |--------------------------------------------------------------------------
    | Báo cáo & Thống kê
    |--------------------------------------------------------------------------
    */
    [
        'name' => 'Báo cáo dự án',
        'flag' => 'reports',
    ],
    [
        'name' => 'Xem báo cáo',
        'flag' => 'reports.view',
        'parent_flag' => 'reports',
    ],
    [
        'name' => 'Xuất báo cáo',
        'flag' => 'reports.export',
        'parent_flag' => 'reports',
    ],
];
```

**1.2 Clear Cache and Refresh Permissions**:
```bash
php artisan cache:clear
php artisan config:clear
php artisan db:seed --class=PermissionSeeder
```

### Step 2: Complete Menu Configuration (1h)

**2.1 Update config/menu.php**:
```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Dự án & Công việc
    |--------------------------------------------------------------------------
    */
    [
        'id' => 'module_project',
        'name' => trans('modules/project::project.menu_name'),
        'route' => null,
        'icon' => 'chart-gantt',
        'sort' => 30,
        'permission' => 'projects.view',
    ],

    // Projects Sub-menu
    [
        'id' => 'module_project_index',
        'name' => trans('modules/project::project.name'),
        'route' => 'projects.index',
        'parent' => 'module_project',
        'icon' => 'folder',
        'sort' => 0,
        'permission' => 'projects.view',
    ],

    // Tasks Sub-menu
    [
        'id' => 'module_task_index',
        'name' => trans('modules/project::task.name'),
        'route' => 'tasks.index',
        'parent' => 'module_project',
        'icon' => 'checklist',
        'sort' => 1,
        'permission' => 'tasks.view',
    ],

    // Kanban View
    [
        'id' => 'module_task_kanban',
        'name' => trans('modules/project::task.kanban'),
        'route' => 'tasks.kanban',
        'parent' => 'module_project',
        'icon' => 'columns',
        'sort' => 2,
        'permission' => 'tasks.view',
    ],

    // Gantt View
    [
        'id' => 'module_project_gantt',
        'name' => trans('modules/project::project.gantt'),
        'route' => 'projects.gantt',
        'parent' => 'module_project',
        'icon' => 'chart-gantt',
        'sort' => 3,
        'permission' => 'projects.view',
    ],

    // Reports (Future)
    [
        'id' => 'module_project_reports',
        'name' => trans('modules/project::reports.name'),
        'route' => 'projects.reports',
        'parent' => 'module_project',
        'icon' => 'chart-bar',
        'sort' => 4,
        'permission' => 'reports.view',
    ],
];
```

**2.2 Clear Menu Cache**:
```bash
php artisan cache:clear
php artisan config:clear
```

### Step 3: Complete Translations (1.5h)

**3.1 Update resources/lang/vi/project.php**:
```php
<?php

return [
    // Menu
    'menu_name' => 'Dự án & Công việc',

    // Project
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
    'actual_start_date' => 'Ngày bắt đầu thực tế',
    'actual_end_date' => 'Ngày kết thúc thực tế',
    'budget' => 'Ngân sách',
    'progress' => 'Tiến độ',
    'gantt' => 'Sơ đồ Gantt',
    'kanban' => 'Bảng Kanban',

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
        'overdue' => 'Quá hạn',
        'add_to_column' => 'Thêm việc vào cột này',
        'no_tasks' => 'Không có công việc',
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

    // Dependency Types
    'dependency' => [
        'finish_to_start' => 'Hoàn thành rồi bắt đầu',
        'start_to_start' => 'Bắt đầu rồi bắt đầu',
        'finish_to_finish' => 'Hoàn thành rồi hoàn thành',
        'start_to_finish' => 'Bắt đầu rồi hoàn thành',
    ],

    // Gantt
    'gantt' => [
        'zoom_day' => 'Ngày',
        'zoom_week' => 'Tuần',
        'zoom_month' => 'Tháng',
        'export_pdf' => 'Xuất PDF',
        'export_png' => 'Xuất hình ảnh',
    ],

    // Dashboard
    'dashboard' => [
        'task_stats' => 'Thống kê công việc',
        'total' => 'Tổng số',
        'in_progress' => 'Đang thực hiện',
        'completed' => 'Hoàn thành',
        'overdue' => 'Quá hạn',
        'upcoming_deadlines' => 'Sắp đến hạn',
        'recent_activity' => 'Hoạt động gần đây',
    ],

    // Notifications
    'notifications' => [
        'overdue_subject' => 'Công việc quá hạn: :task',
        'overdue_greeting' => 'Xin chào :name,',
        'overdue_line_1' => 'Công việc ":task" đã quá hạn :days ngày.',
        'overdue_line_2' => 'Dự án: :project',
        'overdue_footer' => 'Vui lòng cập nhật trạng thái công việc.',
        'view_task' => 'Xem công việc',
    ],

    // Reports
    'reports' => [
        'name' => 'Báo cáo',
        'project_progress' => 'Tiến độ dự án',
        'task_completion' => 'Hoàn thành công việc',
        'overview' => 'Tổng quan',
    ],

    // Messages
    'created_successfully' => 'Tạo thành công',
    'updated_successfully' => 'Cập nhật thành công',
    'deleted_successfully' => 'Xóa thành công',
    'confirm_delete' => 'Bạn có chắc chắn muốn xóa?',
    'no_records_found' => 'Không tìm thấy bản ghi nào',
];
```

**3.2 Update resources/lang/en/project.php**:
```php
<?php

return [
    // Menu
    'menu_name' => 'Projects & Tasks',

    // Project
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
    'actual_start_date' => 'Actual Start Date',
    'actual_end_date' => 'Actual End Date',
    'budget' => 'Budget',
    'progress' => 'Progress',
    'gantt' => 'Gantt Chart',
    'kanban' => 'Kanban Board',

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
        'overdue' => 'Overdue',
        'add_to_column' => 'Add task to this column',
        'no_tasks' => 'No tasks',
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

    // Dependency Types
    'dependency' => [
        'finish_to_start' => 'Finish to Start',
        'start_to_start' => 'Start to Start',
        'finish_to_finish' => 'Finish to Finish',
        'start_to_finish' => 'Start to Finish',
    ],

    // Gantt
    'gantt' => [
        'zoom_day' => 'Day',
        'zoom_week' => 'Week',
        'zoom_month' => 'Month',
        'export_pdf' => 'Export PDF',
        'export_png' => 'Export Image',
    ],

    // Dashboard
    'dashboard' => [
        'task_stats' => 'Task Statistics',
        'total' => 'Total',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'overdue' => 'Overdue',
        'upcoming_deadlines' => 'Upcoming Deadlines',
        'recent_activity' => 'Recent Activity',
    ],

    // Notifications
    'notifications' => [
        'overdue_subject' => 'Overdue Task: :task',
        'overdue_greeting' => 'Hello :name,',
        'overdue_line_1' => 'Task ":task" is :days days overdue.',
        'overdue_line_2' => 'Project: :project',
        'overdue_footer' => 'Please update the task status.',
        'view_task' => 'View Task',
    ],

    // Reports
    'reports' => [
        'name' => 'Reports',
        'project_progress' => 'Project Progress',
        'task_completion' => 'Task Completion',
        'overview' => 'Overview',
    ],

    // Messages
    'created_successfully' => 'Created successfully',
    'updated_successfully' => 'Updated successfully',
    'deleted_successfully' => 'Deleted successfully',
    'confirm_delete' => 'Are you sure you want to delete?',
    'no_records_found' => 'No records found',
];
```

### Step 4: Verify Permissions Work (0.5h)

**4.1 Test Permission Middleware**:
```bash
# Test as admin user
php artisan tinker
>>> $user = User::find(1);
>>> $user->hasPermission('projects.view');
>>> $user->hasPermission('tasks.create');
```

**4.2 Test Menu Visibility**:
- Login as user with different roles
- Verify menu items show/hide based on permissions
- Test all menu items are accessible

### Step 5: Create Documentation (1h)

**5.1 Create README.md**:
```markdown
# Project & Task Management Module

## Overview
This module provides comprehensive project and task management capabilities for Polirium ERP.

## Features
- Hierarchical task structure (unlimited subtasks)
- Multiple views: Table, Kanban, Gantt
- Progress tracking and reporting
- Task dependencies
- Overdue detection and notifications
- Multi-project support

## Installation
Already included in Polirium ERP. No additional installation required.

## Usage
1. Navigate to "Dự án & Công việc" in the sidebar
2. Create a new project
3. Add tasks to the project
4. Switch between Table, Kanban, and Gantt views

## Permissions
- `projects.view` - View projects
- `projects.create` - Create projects
- `projects.edit` - Edit projects
- `projects.delete` - Delete projects
- `tasks.view` - View tasks
- `tasks.create` - Create tasks
- `tasks.edit` - Edit tasks
- `tasks.delete` - Delete tasks
- `tasks.assign` - Assign tasks to users

## Technical Details
- **Module**: `platform/modules/project/`
- **Namespace**: `Polirium\Modules\Project\`
- **Database**: 6 tables (projects, tasks, task_dependencies, task_comments, task_attachments, task_time_logs)
- **UI**: Livewire + Alpine.js + DHTMLX Gantt
```

**5.2 Create User Guide** (Vietnamese):
```markdown
# Hướng dẫn sử dụng Quản lý Dự án & Công việc

## Tạo dự án mới
1. Nhấp menu "Dự án & Công việc" → "Dự án"
2. Nhấp nút "Thêm dự án"
3. Điền thông tin:
   - Tên dự án (bắt buộc)
   - Mô tả
   - Trạng thái (Lên kế hoạch / Đang thực hiện / ...)
   - Mức độ ưu tiên
   - Ngày bắt đầu / Ngày kết thúc
   - Ngân sách
4. Nhấp "Lưu"

## Tạo công việc
1. Chọn dự án từ danh sách
2. Nhấp nút "Thêm công việc"
3. Điền thông tin:
   - Tên công việc
   - Dự án liên quan
   - Công việc cha (nếu là công việc con)
   - Người phụ trách
   - Ngày hạn
   - Thời lượng ước tính
4. Nhấp "Lưu"

## Sử dụng bảng Kanban
- Kéo thả công việc giữa các cột để thay đổi trạng thái
- Nhấp vào công việc để chỉnh sửa
- Sử dụng menu dấu 3 chấm để xóa

## Sử dụng sơ đồ Gantt
- Xem timeline của các công việc
- Kéo thả thanh công việc để đổi lịch
- Kéo mép thanh công việc để đổi thời lượng
- Kéo từ công việc này sang công việc khác để tạo phụ thuộc
```

### Step 6: Final Testing & Deployment Preparation (0.5h)

**6.1 Final Checklist**:
- [ ] All translations complete (vi/en)
- [ ] All permissions configured
- [ ] All menu items visible with correct permissions
- [ ] All routes protected by permission middleware
- [ ] No hardcoded text in views
- [ ] Documentation complete
- [ ] README.md created
- [ ] User guide created

**6.2 Prepare for Production**:
```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run tests
php artisan test --filter=ProjectTest
php artisan test --filter=TaskTest
```

---

## Todo List

### Permissions
- [ ] Update config/permissions.php
- [ ] Add all permission flags
- [ ] Set parent_flag relationships
- [ ] Test permission middleware
- [ ] Assign permissions to roles

### Menu
- [ ] Update config/menu.php
- [ ] Add all menu items
- [ ] Set parent relationships
- [ ] Add permission checks
- [ ] Test menu visibility

### Translations
- [ ] Complete vi/project.php
- [ ] Complete en/project.php
- [ ] Add all missing keys
- [ ] Test language switching
- [ ] Verify no hardcoded strings

### Documentation
- [ ] Create README.md
- [ ] Create user guide (vi)
- [ ] Create developer guide
- [ ] Add API documentation
- [ ] Add screenshots

### Testing
- [ ] Test all permissions
- [ ] Test all menu items
- [ ] Test all translations
- [ ] Test user flows
- [ ] Prepare for production

---

## Success Criteria

### Permissions
- [ ] All 15+ permission flags defined
- [ ] Parent_flag relationships set
- [ ] Middleware protects all routes
- [ ] Users only see permitted content

### Menu
- [ ] Menu items appear in sidebar
- [ ] Icon displays correctly
- [ ] Permission checks work
- [ ] Parent/child relationships correct

### Translations
- [ ] All text translatable
- [ ] vi translations complete
- [ ] en translations complete
- [ ] No hardcoded strings in views
- [ ] Language switching works

### Documentation
- [ ] README.md created
- [ ] User guide created (vi)
- [ ] Developer guide created
- [ ] Screenshots included
- [ ] Usage examples provided

---

## Risk Assessment

| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| Missing translation keys | Low | Medium | Test with `php artisan trans:scan` |
| Permission conflicts | Medium | Low | Test with multiple roles |
| Menu not appearing | Medium | Low | Clear config cache |
| Documentation outdated | Low | High | Keep docs in sync with code |

---

## Security Considerations

1. **Permission Checks**: All routes protected by middleware
2. **Data Access**: Users can only access permitted projects/tasks
3. **Input Validation**: All inputs validated
4. **CSRF**: Laravel CSRF protection enabled
5. **XSS**: Blade auto-escapes output

---

## Next Steps

**Post-Deployment**:
1. Monitor for bugs/issues
2. Gather user feedback
3. Plan feature enhancements
4. Optimize performance
5. Scale as needed

**Future Enhancements**:
- Time tracking integration
- Resource allocation
- Project templates
- Advanced reporting
- Mobile app
- API for third-party integrations

---

## Unresolved Questions

1. Should we add role-based default permissions?
2. How to handle permission upgrades from previous versions?
3. Should we support project-level permissions (vs global)?
4. How to archive old projects (soft delete vs separate table)?

---

**Last Updated**: 2026-01-17
**Next Review**: Post-deployment
**Blocking**: Phase 01-05 (must complete all phases)
**Status**: Final phase - completes the module
