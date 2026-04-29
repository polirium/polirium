# Phase 01: Database Schema & Models

**Date**: 2026-01-17
**Priority**: P1 (Critical Path)
**Estimated Effort**: 8 hours
**Status**: pending

---

## Context Links

**Dependencies**: None (First phase)
**Related Phases**: All subsequent phases depend on this phase
**Research**: `research/researcher-01-pm-features.md` (Section 1: Data Model Best Practices)

---

## Overview

Build the complete database foundation for the Project & Task Management module using Polirium's established patterns (vendor/customer modules).

**Key Objectives**:
1. Create database migrations following Polirium column patterns
2. Build Eloquent models with proper relationships
3. Implement hierarchical task structure (parent_id pattern)
4. Set up task dependencies system
5. Create factories and seeders for testing

---

## Key Insights from Research

### Single Table Pattern for Hierarchical Tasks
- **Source**: researcher-01-pm-features.md, Section 1.1
- **Pattern**: Single `tasks` table with `parent_id` (not separate Tasks/Subtasks)
- **Benefits**: Unlimited nesting, simpler queries, flexible structure

### Recursive Foreign Key
```sql
parent_id BIGINT NULL,
FOREIGN KEY (parent_id) REFERENCES tasks(id)
```

### Task Dependencies
- **Source**: researcher-01-pm-features.md, Section 1.2
- **Pattern**: Separate `task_dependencies` table for predecessor/successor relationships
- **Types**: finish_to_start, start_to_start, finish_to_finish, start_to_finish

---

## Requirements

### Functional Requirements

**FR-01.1**: Projects table with:
- UUID, code (auto-generated), name, description
- Client reference (optional, for future integration)
- Date fields: planned_start, planned_end, actual_start, actual_end
- Budget tracking
- Progress percentage (aggregated from tasks)
- Status workflow: planning, active, on_hold, completed, cancelled
- Multi-branch support
- Creator tracking

**FR-01.2**: Tasks table with:
- UUID, code (auto-generated), name, description
- Foreign keys: project_id, parent_id (recursive), assigned_to
- Date fields: planned_start, planned_end, actual_start, actual_end
- Status workflow: backlog, todo, in_progress, review, done, cancelled
- Priority levels: low, medium, high, urgent
- Progress tracking: estimated_hours, actual_hours, progress_percentage
- Sort order (for Kanban/Task list)
- Multi-branch support

**FR-01.3**: Task dependencies table with:
- Predecessor/successor relationships
- Dependency type enum
- Lag days (offset time)

**FR-01.4**: Supporting tables:
- task_comments (threaded discussions)
- task_attachments (file uploads)
- task_time_logs (hours tracking)

### Non-Functional Requirements

**NFR-01.1**: Follow Polirium naming conventions
**NFR-01.2**: Use BaseModel traits (HasUuid, LogsActivity)
**NFR-01.3**: Proper indexes for performance
**NFR-01.4**: Foreign key constraints with cascade actions

---

## Architecture

### Database Schema

```sql
-- Projects table
CREATE TABLE projects (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    uuid VARCHAR(36) UNIQUE,
    code VARCHAR(50) UNIQUE,
    name VARCHAR(255),
    description TEXT,
    client_id BIGINT UNSIGNED NULL,  -- Future: link to customers
    status ENUM('planning', 'active', 'on_hold', 'completed', 'cancelled') DEFAULT 'planning',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',

    -- Date fields
    planned_start_date DATE NULL,
    planned_end_date DATE NULL,
    actual_start_date DATE NULL,
    actual_end_date DATE NULL,

    -- Budget & Progress
    budget DECIMAL(15,2) DEFAULT 0,
    progress_percentage DECIMAL(5,2) DEFAULT 0,

    -- Metadata
    branch_id BIGINT UNSIGNED NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    INDEX idx_projects_status (status),
    INDEX idx_projects_branch (branch_id),
    INDEX idx_projects_created_by (created_by),
    FOREIGN KEY (branch_id) REFERENCES branches(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (updated_by) REFERENCES users(id)
);

-- Tasks table (hierarchical via parent_id)
CREATE TABLE tasks (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    uuid VARCHAR(36) UNIQUE,
    code VARCHAR(50) UNIQUE,
    project_id BIGINT UNSIGNED NOT NULL,
    parent_id BIGINT UNSIGNED NULL,  -- Recursive FK for hierarchy
    name VARCHAR(255),
    description TEXT,
    status ENUM('backlog', 'todo', 'in_progress', 'review', 'done', 'cancelled') DEFAULT 'backlog',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',

    -- Assignment
    assigned_to BIGINT UNSIGNED NULL,

    -- Date fields
    planned_start_date DATE NULL,
    planned_end_date DATE NULL,
    actual_start_date DATE NULL,
    actual_end_date DATE NULL,

    -- Progress tracking
    estimated_hours DECIMAL(10,2) DEFAULT 0,
    actual_hours DECIMAL(10,2) DEFAULT 0,
    progress_percentage DECIMAL(5,2) DEFAULT 0,
    sort_order INT DEFAULT 0,

    -- Metadata
    branch_id BIGINT UNSIGNED NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    INDEX idx_tasks_project (project_id),
    INDEX idx_tasks_parent (parent_id),
    INDEX idx_tasks_status (status),
    INDEX idx_tasks_assigned (assigned_to),
    INDEX idx_tasks_branch (branch_id),
    INDEX idx_tasks_sort (sort_order),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id),
    FOREIGN KEY (branch_id) REFERENCES branches(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (updated_by) REFERENCES users(id)
);

-- Task dependencies
CREATE TABLE task_dependencies (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    predecessor_id BIGINT UNSIGNED NOT NULL,
    successor_id BIGINT UNSIGNED NOT NULL,
    dependency_type ENUM('finish_to_start', 'start_to_start', 'finish_to_finish', 'start_to_finish') DEFAULT 'finish_to_start',
    lag_days INT DEFAULT 0,

    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    UNIQUE KEY unique_dependency (predecessor_id, successor_id),
    INDEX idx_deps_predecessor (predecessor_id),
    INDEX idx_deps_successor (successor_id),
    FOREIGN KEY (predecessor_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (successor_id) REFERENCES tasks(id) ON DELETE CASCADE
);

-- Task comments
CREATE TABLE task_comments (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    task_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    content TEXT NOT NULL,

    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    INDEX idx_comments_task (task_id),
    INDEX idx_comments_user (user_id),
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Task attachments
CREATE TABLE task_attachments (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    task_id BIGINT UNSIGNED NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_size BIGINT UNSIGNED NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    uploaded_by BIGINT UNSIGNED NOT NULL,

    created_at TIMESTAMP NULL,

    INDEX idx_attachments_task (task_id),
    INDEX idx_attachments_uploaded (uploaded_by),
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
);

-- Task time logs
CREATE TABLE task_time_logs (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    task_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    hours DECIMAL(10,2) NOT NULL,
    log_date DATE NOT NULL,
    description TEXT NULL,

    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    INDEX idx_timelogs_task (task_id),
    INDEX idx_timelogs_user (user_id),
    INDEX idx_timelogs_date (log_date),
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

### Model Structure

```php
// Project.php
namespace Polirium\Modules\Project\Http\Models;

class Project extends BaseModel
{
    protected $table = 'projects';

    protected $fillable = [
        'uuid', 'code', 'name', 'description',
        'client_id', 'status', 'priority',
        'planned_start_date', 'planned_end_date',
        'actual_start_date', 'actual_end_date',
        'budget', 'progress_percentage',
        'branch_id', 'created_by', 'updated_by',
    ];

    // Relationships
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class)->orderBy('sort_order');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'client_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Accessors
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'planning' => __('modules/project::status.planning'),
            'active' => __('modules/project::status.active'),
            'on_hold' => __('modules/project::status.on_hold'),
            'completed' => __('modules/project::status.completed'),
            'cancelled' => __('modules/project::status.cancelled'),
            default => $this->status,
        };
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}

// Task.php
namespace Polirium\Modules\Project\Http\Models;

class Task extends BaseModel
{
    protected $table = 'tasks';

    protected $fillable = [
        'uuid', 'code', 'project_id', 'parent_id',
        'name', 'description', 'status', 'priority',
        'assigned_to',
        'planned_start_date', 'planned_end_date',
        'actual_start_date', 'actual_end_date',
        'estimated_hours', 'actual_hours', 'progress_percentage',
        'sort_order', 'branch_id', 'created_by', 'updated_by',
    ];

    // Relationships
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_id')->orderBy('sort_order');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function dependencies(): HasMany
    {
        return $this->hasMany(TaskDependency::class, 'predecessor_id');
    }

    public function dependents(): HasMany
    {
        return $this->hasMany(TaskDependency::class, 'successor_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class);
    }

    public function timeLogs(): HasMany
    {
        return $this->hasMany(TaskTimeLog::class);
    }

    // Accessors
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'backlog' => __('modules/project::task.status.backlog'),
            'todo' => __('modules/project::task.status.todo'),
            'in_progress' => __('modules/project::task.status.in_progress'),
            'review' => __('modules/project::task.status.review'),
            'done' => __('modules/project::task.status.done'),
            'cancelled' => __('modules/project::task.status.cancelled'),
            default => $this->status,
        };
    }

    public function getIsOverdueAttribute(): bool
    {
        if (!$this->planned_end_date || in_array($this->status, ['done', 'cancelled'])) {
            return false;
        }
        return now()->greaterThan($this->planned_end_date);
    }

    // Scopes
    public function scopeRoot(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('planned_end_date', '<', now())
            ->whereNotIn('status', ['done', 'cancelled']);
    }
}
```

---

## Related Code Files

### Reference Files (Study These)

**Vendor Module Patterns**:
- `/Users/vingamagic/Developer/php/polirium/platform/modules/vendor/database/migrations/2024_11_06_154144_vendor_create_vendor_table.php`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/vendor/src/Http/Model/Vendor.php`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/vendor/src/Http/Model/VendorGroup.php`

**Core Base Models**:
- `/Users/vingamagic/Developer/php/polirium/platform/core/base/src/Http/Models/BaseModel.php`
- `/Users/vingamagic/Developer/php/polirium/platform/core/base/src/Http/Models/User.php`

### Files to Create

**Migrations**:
- `/Users/vingamagic/Developer/php/polirium/platform/modules/project/database/migrations/2026_01_17_000001_create_projects_table.php`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/project/database/migrations/2026_01_17_000002_create_tasks_table.php`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/project/database/migrations/2026_01_17_000003_create_task_dependencies_table.php`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/project/database/migrations/2026_01_17_000004_create_task_comments_table.php`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/project/database/migrations/2026_01_17_000005_create_task_attachments_table.php`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/project/database/migrations/2026_01_17_000006_create_task_time_logs_table.php`

**Models**:
- `/Users/vingamagic/Developer/php/polirium/platform/modules/project/src/Http/Models/Project.php`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/project/src/Http/Models/Task.php`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/project/src/Http/Models/TaskDependency.php`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/project/src/Http/Models/TaskComment.php`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/project/src/Http/Models/TaskAttachment.php`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/project/src/Http/Models/TaskTimeLog.php`

**Factories**:
- `/Users/vingamagic/Developer/php/polirium/platform/modules/project/database/factories/ProjectFactory.php`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/project/database/factories/TaskFactory.php`

**Seeders**:
- `/Users/vingamagic/Developer/php/polirium/platform/modules/project/database/seeders/ProjectSeeder.php`

---

## Implementation Steps

### Step 1: Create Module Directory Structure (1h)
```bash
# Create base structure
mkdir -p platform/modules/project/{database/migrations,src/Http/Models,database/factories,database/seeders,resources/lang/{en,vi}}

# Copy composer.json template from vendor module
cp platform/modules/vendor/composer.json platform/modules/project/composer.json
# Edit: Update namespace to Polirium\Modules\Project
```

### Step 2: Create Service Provider (1h)
```bash
# Create ServiceProvider
touch platform/modules/project/src/Providers/ProjectServiceProvider.php
```

```php
<?php
namespace Polirium\Modules\Project\Providers;

use Polirium\Core\Support\Providers\PoliriumBaseServiceProvider;

class ProjectServiceProvider extends PoliriumBaseServiceProvider
{
    public function boot(): void
    {
        $this
            ->setNamespace('modules/project')
            ->loadConfigurations(['project', 'livewire', 'menu', 'permissions'])
            ->loadViews()
            ->loadTranslations()
            ->loadRoutes(['web'])
            ->loadMigrations();
    }
}
```

### Step 3: Create Migrations (3h)

**3.1 Projects Migration**:
```bash
php artisan make:migration create_projects_table --path=platform/modules/project/database/migrations
```

**3.2 Tasks Migration**:
```bash
php artisan make:migration create_tasks_table --path=platform/modules/project/database/migrations
```

**3.3 Supporting Migrations**:
- task_dependencies, task_comments, task_attachments, task_time_logs

**3.4 Run Migrations**:
```bash
php artisan migrate
# Verify tables created correctly
php artisan db:show
```

### Step 4: Create Models (2h)

**4.1 Base Models**:
- Project.php with relationships, scopes, accessors
- Task.php with recursive relationship (parent/children)
- TaskDependency, TaskComment, TaskAttachment, TaskTimeLog

**4.2 Test Relationships**:
```php
// In tinker
$project = Project::first();
$project->tasks; // Should return collection

$task = Task::first();
$task->parent; // Should return parent task
$task->children; // Should return child tasks
```

### Step 5: Create Factories & Seeders (1h)

**5.1 Factories**:
```php
// ProjectFactory.php
public function definition(): array
{
    return [
        'uuid' => Str::uuid(),
        'code' => 'PRJ' . str_pad($this->faker->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
        'name' => $this->faker->sentence(3),
        'description' => $this->faker->paragraph(),
        'status' => $this->faker->randomElement(['planning', 'active', 'on_hold', 'completed', 'cancelled']),
        'priority' => $this->faker->randomElement(['low', 'medium', 'high', 'urgent']),
        'planned_start_date' => $this->faker->dateTimeBetween('now', '+1 month'),
        'planned_end_date' => $this->faker->dateTimeBetween('+1 month', '+3 months'),
        'budget' => $this->faker->randomFloat(2, 1000, 100000),
        'progress_percentage' => $this->faker->randomFloat(2, 0, 100),
        'branch_id' => Branch::inRandomOrder()->first()?->id,
        'created_by' => User::inRandomOrder()->first()?->id,
    ];
}

// TaskFactory.php
public function definition(): array
{
    $project = Project::inRandomOrder()->first() ?? Project::factory()->create();

    return [
        'uuid' => Str::uuid(),
        'code' => 'TSK' . str_pad($this->faker->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
        'project_id' => $project->id,
        'parent_id' => null, // Set in state
        'name' => $this->faker->sentence(3),
        'description' => $this->faker->paragraph(),
        'status' => $this->faker->randomElement(['backlog', 'todo', 'in_progress', 'review', 'done']),
        'priority' => $this->faker->randomElement(['low', 'medium', 'high', 'urgent']),
        'assigned_to' => User::inRandomOrder()->first()?->id,
        'planned_start_date' => $this->faker->dateTimeBetween('now', '+1 month'),
        'planned_end_date' => $this->faker->dateTimeBetween('+1 month', '+2 months'),
        'estimated_hours' => $this->faker->randomFloat(2, 1, 100),
        'actual_hours' => $this->faker->randomFloat(2, 0, 50),
        'progress_percentage' => $this->faker->randomFloat(2, 0, 100),
        'sort_order' => $this->faker->numberBetween(0, 100),
        'branch_id' => $project->branch_id,
        'created_by' => User::inRandomOrder()->first()?->id,
    ];
}
```

**5.2 Seeder**:
```php
// ProjectSeeder.php
public function run(): void
{
    Project::factory(10)->create()->each(function ($project) {
        // Create root tasks
        Task::factory(5)->create([
            'project_id' => $project->id,
            'parent_id' => null,
        ])->each(function ($task) {
            // Create subtasks
            Task::factory(3)->create([
                'project_id' => $task->project_id,
                'parent_id' => $task->id,
            ]);
        });
    });
}
```

**5.3 Run Seeder**:
```bash
php artisan db:seed --class=ProjectSeeder
```

### Step 6: Verify & Test (1h)

**6.1 Database Verification**:
```sql
-- Check tables
SHOW TABLES LIKE '%project%';
SHOW TABLES LIKE '%task%';

-- Check indexes
SHOW INDEX FROM projects;
SHOW INDEX FROM tasks;

-- Check foreign keys
SELECT CONSTRAINT_NAME, TABLE_NAME, REFERENCED_TABLE_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'polirium' AND TABLE_NAME LIKE '%task%';
```

**6.2 Model Relationship Tests**:
```php
// In tinker
use Polirium\Modules\Project\Http\Models\{Project, Task};

// Test Project -> Tasks
$project = Project::with('tasks')->first();
dd($project->tasks);

// Test Task Hierarchy
$rootTask = Task::root()->first();
dd($rootTask->children);

// Test Overdue Scope
$overdueTasks = Task::overdue()->get();
dd($overdueTasks);

// Test Dependencies
$task = Task::with(['dependencies', 'dependents'])->first();
dd($task->dependencies);
```

**6.3 Factory Output**:
```bash
# Seed test data
php artisan db:seed --class=ProjectSeeder

# Verify
php artisan tinker
>>> Project::count()
>>> Task::count()
>>> Task::whereNotNull('parent_id')->count() # Should have subtasks
```

---

## Todo List

### Database Schema
- [ ] Create projects table migration
- [ ] Create tasks table migration with parent_id
- [ ] Create task_dependencies table
- [ ] Create task_comments table
- [ ] Create task_attachments table
- [ ] Create task_time_logs table
- [ ] Add indexes for performance
- [ ] Add foreign key constraints
- [ ] Run migrations successfully

### Models
- [ ] Create Project model extending BaseModel
- [ ] Create Task model with recursive relationships
- [ ] Create TaskDependency model
- [ ] Create TaskComment model
- [ ] Create TaskAttachment model
- [ ] Create TaskTimeLog model
- [ ] Add all relationships (belongsTo, hasMany, belongsToMany)
- [ ] Add accessors (status_label, priority_label, is_overdue)
- [ ] Add scopes (active, overdue, root)

### Factories & Seeders
- [ ] Create ProjectFactory
- [ ] Create TaskFactory with parent/child states
- [ ] Create ProjectSeeder
- [ ] Test factories generate realistic data
- [ ] Test hierarchical structure (parent_id)

### Testing
- [ ] Verify all migrations run
- [ ] Test recursive parent-child relationships
- [ ] Test foreign key cascades
- [ ] Test scopes (active, overdue, root)
- [ ] Test accessors return correct labels
- [ ] Seed test data successfully

---

## Success Criteria

### Database
- [ ] All 6 tables created successfully
- [ ] Foreign key constraints work (cascade deletes)
- [ ] Indexes created on frequently queried columns
- [ ] No orphaned records after cascade operations

### Models
- [ ] Project->tasks relationship returns collection
- [ ] Task->parent returns parent task or null
- [ ] Task->children returns child tasks
- [ ] Recursive queries work without infinite loops
- [ ] Overdue scope correctly identifies overdue tasks

### Test Data
- [ ] Factories generate 10+ projects
- [ ] Each project has 5+ root tasks
- [ ] Each root task has 3+ subtasks
- [ ] Test data includes all status/priority combinations
- [ ] Date fields are realistic (not in past for new records)

---

## Risk Assessment

| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| Circular dependencies in tasks | High | Low | Add validation to prevent cycles |
| Deep hierarchy performance | Medium | Medium | Limit depth to 5 levels in UI |
| Foreign key cascade issues | High | Low | Test cascade operations thoroughly |
| Factory state complexity | Low | Medium | Use separate factory states for parent/child |

---

## Security Considerations

1. **SQL Injection**: Use Eloquent ORM (parameterized queries)
2. **Mass Assignment**: Only fillable fields are mass-assignable
3. **Foreign Keys**: Prevent orphaned records via FK constraints
4. **Access Control**: Permissions added in Phase 06

---

## Next Steps

**Immediate**:
1. Create module directory structure
2. Write and run migrations
3. Create models with relationships
4. Build factories and seeders
5. Test with sample data

**Phase 02** (After this phase):
1. Create Table components (PowerGrid)
2. Build CRUD modals
3. Implement filter sidebar
4. Add permissions

---

## Unresolved Questions

1. Should we use `client_id` in projects table (future Customer integration)?
2. Should we add `team_id` for team assignment (vs individual users)?
3. How to handle project templates (clone project structure)?
4. Should tasks support multiple assignees (many-to-many)?

---

**Last Updated**: 2026-01-17
**Next Review**: After migrations completed
**Blocking**: None (first phase)
