# Phase 05: Progress Tracking & Notifications

**Date**: 2026-01-17
**Priority**: P2
**Estimated Effort**: 8 hours
**Status**: pending

---

## Context Links

**Dependencies**: Phase 01 (Database), Phase 02 (CRUD)
**Related Phases**: Phase 03 (Kanban), Phase 04 (Gantt), Phase 06 (Permissions)
**Research**: `research/researcher-01-pm-features.md` (Sections 3 & 4: Progress & Notifications)

---

## Overview

Implement progress calculation algorithms, delay detection, and notification system for task management.

**Key Objectives**:
1. Calculate task progress % (subtask aggregation, effort-based)
2. Detect overdue tasks and delays
3. Implement notification system (overdue, upcoming deadlines)
4. Create dashboard widgets (task stats, progress charts)
5. Schedule automated alerts (daily/weekly summaries)

---

## Key Insights from Research

### Progress Calculation Methods
- **Source**: researcher-01-pm-features.md, Section 3.1
- **Subtask Aggregation**: For parent tasks (weighted average of children)
- **Effort-Based**: For leaf tasks (actual_hours / estimated_hours)
- **Manual Override**: Allow users to set % directly
- **Formula**: `progress = SUM(child.progress * child.weight) / SUM(child.weight)`

### Delay Detection
- **Source**: researcher-01-pm-features.md, Section 3.3
- **Detection Logic**: `if (now() > planned_end_date && status != 'done')`
- **Severity Levels**: Warning (1-3 days), Critical (4-7 days), Severe (8+ days)
- **Actions**: Notifications, dashboard updates, escalation

### Notification Patterns
- **Source**: researcher-01-pm-features.md, Section 4.1
- **Triggers**: Task overdue, upcoming deadline, progress stagnation
- **Channels**: In-app + Email + Slack/Teams (future)
- **Frequency**: Instant + daily/weekly summaries
- **Recipients**: Assignee (primary), PM, stakeholders (escalation)

---

## Requirements

### Functional Requirements

**FR-05.1**: Progress Calculation
- Auto-calculate progress for parent tasks from subtasks
- Support effort-based calculation for leaf tasks
- Allow manual override with audit trail
- Recalculate on status change, time log, subtask update

**FR-05.2**: Delay Detection
- Detect overdue tasks (planned_end_date < now && status != 'done')
- Calculate days overdue
- Assign severity levels (warning/critical/severe)
- Update dashboard counters

**FR-05.3**: Notifications
- Send instant alert when task becomes overdue
- Send daily digest of overdue tasks (9 AM)
- Send upcoming deadline warnings (3 days, 1 day before)
- Send progress stagnation alerts (no progress 7+ days)
- Escalate to PM after 3 days overdue
- Escalate to stakeholders after 7 days

**FR-05.4**: Dashboard Widgets
- Task stats (total, overdue, completed, in progress)
- Progress charts (project completion %)
- Upcoming deadlines (next 7 days)
- Recent activity feed

**FR-05.5**: Scheduled Jobs
- Daily job: Check for overdue tasks, send notifications
- Weekly job: Send summary reports
- Hourly job: Update progress calculations

### Non-Functional Requirements

**NFR-05.1**: Progress recalculation <1s for 1000 tasks
**NFR-05.2**: Notifications sent within 5 min of trigger
**NFR-05.3**: Dashboard widgets load <2s
**NFR-05.4**: Audit trail for all progress changes

---

## Architecture

### Service Classes

```
Platform\Modules\Project\Services\
├── ProgressCalculationService.php
├── DelayDetectionService.php
└── NotificationService.php
```

### Jobs

```
Platform\Modules\Project\Jobs\
├── CalculateProgressJob.php
├── CheckOverdueTasksJob.php
└── SendDailyDigestJob.php
```

### Dashboard Components

```
Platform\Modules\Project\Http\Livewire\Dashboard\
├── TaskStatsWidget.php
├── ProgressChartWidget.php
├── UpcomingDeadlinesWidget.php
└── RecentActivityWidget.php
```

---

## Related Code Files

### Reference Files (Study These)

**Core Notification System**:
- `/Users/vingamagic/Developer/php/polirium/platform/core/base/src/Http/Models/Notification.php`
- Existing notification patterns in codebase

**Scheduled Jobs**:
- `/Users/vingamagic/Developer/php/polirium/platform/modules/*/app/Jobs/`

### Files to Create

**Services**:
- `/Users/vingamagic/Developer/php/polirium/platform/modules/project/src/Services/ProgressCalculationService.php`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/project/src/Services/DelayDetectionService.php`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/project/src/Services/NotificationService.php`

**Jobs**:
- `/Users/vingamagic/Developer/php/polirium/platform/modules/project/src/Jobs/CalculateProgressJob.php`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/project/src/Jobs/CheckOverdueTasksJob.php`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/project/src/Jobs/SendDailyDigestJob.php`

**Dashboard Widgets**:
- `/Users/vingamagic/Developer/php/polirium/platform/modules/project/src/Http/Livewire/Dashboard/TaskStatsWidget.php`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/project/src/Http/Livewire/Dashboard/ProgressChartWidget.php`

**Migrations**:
- `/Users/vingamagic/Developer/php/polirium/platform/modules/project/database/migrations/2026_01_17_000007_add_progress_audit_table.php`

---

## Implementation Steps

### Step 1: Create Progress Audit Table (0.5h)

**1.1 Migration**:
```php
// create_task_progress_audits_table.php
Schema::create('task_progress_audits', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('task_id');
    $table->decimal('old_progress', 5, 2);
    $table->decimal('new_progress', 5, 2);
    $table->string('change_reason')->nullable();
    $table->enum('change_type', ['auto', 'manual', 'status_change', 'time_log']);
    $table->unsignedBigInteger('changed_by')->nullable();
    $table->timestamps();

    $table->index('task_id');
    $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');
    $table->foreign('changed_by')->references('id')->on('users');
});
```

### Step 2: Create Progress Calculation Service (2h)

**2.1 ProgressCalculationService.php**:
```php
<?php

namespace Polirium\Modules\Project\Services;

use Polirium\Modules\Project\Http\Models\Task;
use Polirium\Modules\Project\Http\Models\Project;
use Illuminate\Support\Facades\DB;

class ProgressCalculationService
{
    /**
     * Calculate progress for a task
     */
    public function calculateTaskProgress(Task $task): float
    {
        // If task has subtasks, aggregate from children
        if ($task->children()->count() > 0) {
            return $this->calculateFromSubtasks($task);
        }

        // Leaf task: use effort-based calculation
        return $this->calculateFromEffort($task);
    }

    /**
     * Calculate progress from subtasks (weighted average)
     */
    private function calculateFromSubtasks(Task $task): float
    {
        $children = $task->children;

        if ($children->isEmpty()) {
            return $task->progress_percentage;
        }

        $totalWeight = 0;
        $weightedProgress = 0;

        foreach ($children as $child) {
            $weight = $child->estimated_hours ?: 1;
            $childProgress = $this->calculateTaskProgress($child);

            $weightedProgress += $childProgress * $weight;
            $totalWeight += $weight;
        }

        return $totalWeight > 0 ? round($weightedProgress / $totalWeight, 2) : 0;
    }

    /**
     * Calculate progress from effort (actual / estimated)
     */
    private function calculateFromEffort(Task $task): float
    {
        if ($task->estimated_hours <= 0) {
            // If no estimate, use status-based estimation
            return $this->calculateFromStatus($task);
        }

        $progress = ($task->actual_hours / $task->estimated_hours) * 100;
        return min(round($progress, 2), 100);
    }

    /**
     * Calculate progress from status (fallback)
     */
    private function calculateFromStatus(Task $task): float
    {
        return match ($task->status) {
            'backlog' => 0,
            'todo' => 10,
            'in_progress' => 50,
            'review' => 90,
            'done' => 100,
            'cancelled' => 0,
            default => 0,
        };
    }

    /**
     * Update task progress with audit trail
     */
    public function updateTaskProgress(Task $task, float $newProgress, string $changeType = 'auto', ?string $reason = null, ?int $changedBy = null): void
    {
        $oldProgress = $task->progress_percentage;

        if ($oldProgress == $newProgress) {
            return; // No change
        }

        // Update task
        $task->progress_percentage = $newProgress;
        $task->save();

        // Audit trail
        DB::table('task_progress_audits')->insert([
            'task_id' => $task->id,
            'old_progress' => $oldProgress,
            'new_progress' => $newProgress,
            'change_reason' => $reason,
            'change_type' => $changeType,
            'changed_by' => $changedBy ?? auth()->id(),
            'created_at' => now(),
        ]);

        // Recalculate parent progress
        if ($task->parent) {
            $this->updateTaskProgress($task->parent, $this->calculateTaskProgress($task->parent), 'auto', 'Child task updated');
        }

        // Update project progress
        if ($task->project) {
            $this->updateProjectProgress($task->project);
        }
    }

    /**
     * Update project progress (aggregate from root tasks)
     */
    public function updateProjectProgress(Project $project): void
    {
        $rootTasks = $project->tasks()->whereNull('parent_id')->get();

        if ($rootTasks->isEmpty()) {
            $project->progress_percentage = 0;
        } else {
            $totalProgress = $rootTasks->sum('progress_percentage');
            $project->progress_percentage = round($totalProgress / $rootTasks->count(), 2);
        }

        $project->save();
    }

    /**
     * Recalculate all tasks (for scheduled job)
     */
    public function recalculateAll(): void
    {
        Task::whereNull('parent_id')
            ->with(['children', 'project'])
            ->chunk(100, function ($tasks) {
                foreach ($tasks as $task) {
                    $this->updateTaskProgress($task, $this->calculateTaskProgress($task), 'auto', 'Scheduled recalculation');
                }
            });
    }
}
```

### Step 3: Create Delay Detection Service (1.5h)

**3.1 DelayDetectionService.php**:
```php
<?php

namespace Polirium\Modules\Project\Services;

use Polirium\Modules\Project\Http\Models\Task;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DelayDetectionService
{
    /**
     * Check for overdue tasks
     */
    public function checkOverdueTasks(): array
    {
        $overdueTasks = Task::overdue()->with(['project', 'assignedTo'])->get();

        $results = [
            'warning' => [],     // 1-3 days overdue
            'critical' => [],    // 4-7 days overdue
            'severe' => [],      // 8+ days overdue
        ];

        foreach ($overdueTasks as $task) {
            $daysOverdue = Carbon::now()->diffInDays($task->planned_end_date);

            if ($daysOverdue <= 3) {
                $results['warning'][] = $task;
            } elseif ($daysOverdue <= 7) {
                $results['critical'][] = $task;
            } else {
                $results['severe'][] = $task;
            }
        }

        return $results;
    }

    /**
     * Get tasks due soon (within X days)
     */
    public function getUpcomingDeadlines(int $days = 7): array
    {
        $endDate = Carbon::now()->addDays($days);

        return Task::whereNotIn('status', ['done', 'cancelled'])
            ->whereBetween('planned_end_date', [Carbon::now(), $endDate])
            ->with(['project', 'assignedTo'])
            ->orderBy('planned_end_date')
            ->get()
            ->groupBy(function ($task) {
                return $task->planned_end_date->format('Y-m-d');
            })
            ->toArray();
    }

    /**
     * Get tasks with no progress for X days
     */
    public function getStagnantTasks(int $days = 7): array
    {
        $threshold = Carbon::now()->subDays($days);

        return Task::where('status', 'in_progress')
            ->where('updated_at', '<', $threshold)
            ->with(['project', 'assignedTo'])
            ->get()
            ->toArray();
    }

    /**
     * Calculate schedule variance (SV) for project
     * SV = Earned Value - Planned Value
     * Negative SV = Behind schedule
     */
    public function calculateScheduleVariance(Project $project): array
    {
        $totalTasks = $project->tasks()->count();
        $completedTasks = $project->tasks()->where('status', 'done')->count();

        if ($totalTasks === 0) {
            return ['sv' => 0, 'sv_percent' => 0, 'status' => 'on_track'];
        }

        $plannedValue = $totalTasks; // Simplified
        $earnedValue = $completedTasks;
        $scheduleVariance = $earnedValue - $plannedValue;
        $svPercent = ($scheduleVariance / $plannedValue) * 100;

        $status = match (true) {
            $svPercent >= 0 => 'ahead',
            $svPercent >= -10 => 'on_track',
            $svPercent >= -25 => 'behind',
            default => 'severe',
        };

        return [
            'sv' => $scheduleVariance,
            'sv_percent' => round($svPercent, 2),
            'status' => $status,
        ];
    }
}
```

### Step 4: Create Notification Service (1.5h)

**4.1 NotificationService.php**:
```php
<?php

namespace Polirium\Modules\Project\Services;

use Polirium\Modules\Project\Http\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Polirium\Modules\Project\Notifications\TaskOverdueNotification;
use Polirium\Modules\Project\Notifications\UpcomingDeadlineNotification;

class NotificationService
{
    /**
     * Send overdue task notification
     */
    public function sendOverdueNotification(Task $task, int $daysOverdue): void
    {
        // Notify assignee
        if ($task->assignedTo) {
            $task->assignedTo->notify(new TaskOverdueNotification($task, $daysOverdue));
        }

        // Escalate to PM if overdue > 3 days
        if ($daysOverdue > 3) {
            $this->notifyProjectManagers($task, 'task_overdue_escalation');
        }

        // Escalate to stakeholders if overdue > 7 days
        if ($daysOverdue > 7) {
            $this->notifyStakeholders($task, 'task_overdue_critical');
        }
    }

    /**
     * Send upcoming deadline notification
     */
    public function sendUpcomingDeadlineNotification(Task $task, int $daysUntilDue): void
    {
        if ($task->assignedTo) {
            $task->assignedTo->notify(new UpcomingDeadlineNotification($task, $daysUntilDue));
        }
    }

    /**
     * Send daily digest of overdue tasks
     */
    public function sendDailyDigest(User $user, array $overdueTasks): void
    {
        if (empty($overdueTasks)) {
            return;
        }

        $user->notify(new DailyDigestNotification($overdueTasks));
    }

    /**
     * Notify project managers
     */
    private function notifyProjectManagers(Task $task, string $type): void
    {
        $pmRole = config('project.pm_role', 'admin');
        $pms = User::role($pmRole)->get();

        foreach ($pms as $pm) {
            $pm->notify(new TaskEscalationNotification($task, $type));
        }
    }

    /**
     * Notify stakeholders (if implemented)
     */
    private function notifyStakeholders(Task $task, string $type): void
    {
        // Future: Send to stakeholders table
    }
}
```

**4.2 Create Notification Classes**:
```php
// app/Notifications/TaskOverdueNotification.php
<?php

namespace Polirium\Modules\Project\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Polirium\Modules\Project\Http\Models\Task;

class TaskOverdueNotification extends Notification
{
    public function __construct(
        public Task $task,
        public int $daysOverdue
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('modules/project::notifications.overdue_subject', [
                'task' => $task->name
            ]))
            ->greeting(__('modules/project::notifications.overdue_greeting', [
                'name' => $notifiable->name
            ]))
            ->line(__('modules/project::notifications.overdue_line_1', [
                'task' => $task->name,
                'days' => $this->daysOverdue
            ]))
            ->line(__('modules/project::notifications.overdue_line_2', [
                'project' => $task->project->name
            ]))
            ->action(__('modules/project::notifications.view_task'), route('tasks.index'))
            ->line(__('modules/project::notifications.overdue_footer'));
    }

    public function toArray($notifiable): array
    {
        return [
            'task_id' => $this->task->id,
            'task_name' => $this->task->name,
            'project_id' => $this->task->project_id,
            'project_name' => $this->task->project->name,
            'days_overdue' => $this->daysOverdue,
            'type' => 'task_overdue',
        ];
    }
}
```

### Step 5: Create Scheduled Jobs (1h)

**5.1 CheckOverdueTasksJob.php**:
```php
<?php

namespace Polirium\Modules\Project\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Polirium\Modules\Project\Services\DelayDetectionService;
use Polirium\Modules\Project\Services\NotificationService;
use Polirium\Modules\Project\Services\ProgressCalculationService;

class CheckOverdueTasksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        $this->onQueue('notifications');
    }

    public function handle(
        DelayDetectionService $delayService,
        NotificationService $notificationService,
        ProgressCalculationService $progressService
    ): void {
        // Recalculate progress first
        $progressService->recalculateAll();

        // Check for overdue tasks
        $overdue = $delayService->checkOverdueTasks();

        // Send notifications for each severity
        foreach ($overdue['severe'] as $task) {
            $notificationService->sendOverdueNotification($task, $task->days_overdue);
        }

        // Log summary
        Log::info('Overdue tasks check completed', [
            'warning' => count($overdue['warning']),
            'critical' => count($overdue['critical']),
            'severe' => count($overdue['severe']),
        ]);
    }
}
```

**5.2 SendDailyDigestJob.php**:
```php
<?php

namespace Polirium\Modules\Project\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Polirium\Modules\Project\Services\DelayDetectionService;
use Polirium\Modules\Project\Services\NotificationService;
use App\Models\User;

class SendDailyDigestJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function handle(
        DelayDetectionService $delayService,
        NotificationService $notificationService
    ): void {
        $overdue = $delayService->checkOverdueTasks();
        $allOverdue = array_merge(
            $overdue['warning'],
            $overdue['critical'],
            $overdue['severe']
        );

        // Group by assignee
        $byAssignee = collect($allOverdue)->groupBy('assigned_to');

        foreach ($byAssignee as $assigneeId => $tasks) {
            $user = User::find($assigneeId);
            if ($user) {
                $notificationService->sendDailyDigest($user, $tasks->toArray());
            }
        }
    }
}
```

**5.3 Schedule Jobs in Kernel**:
```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // Check for overdue tasks every hour
    $schedule->job(new \Polirium\Modules\Project\Jobs\CheckOverdueTasksJob())
        ->hourly();

    // Send daily digest at 9 AM
    $schedule->job(new \Polirium\Modules\Project\Jobs\SendDailyDigestJob())
        ->dailyAt('09:00');

    // Recalculate progress every 6 hours
    $schedule->job(new \Polirium\Modules\Project\Jobs\CalculateProgressJob())
        ->everySixHours();
}
```

### Step 6: Create Dashboard Widgets (1.5h)

**6.1 TaskStatsWidget.php**:
```php
<?php

namespace Polirium\Modules\Project\Http\Livewire\Dashboard;

use Livewire\Component;
use Polirium\Modules\Project\Services\DelayDetectionService;
use Polirium\Modules\Project\Http\Models\Task;

class TaskStatsWidget extends Component
{
    public int $totalTasks = 0;
    public int $overdueTasks = 0;
    public int $completedTasks = 0;
    public int $inProgressTasks = 0;

    public function mount(DelayDetectionService $delayService)
    {
        $this->totalTasks = Task::count();
        $this->overdueTasks = Task::overdue()->count();
        $this->completedTasks = Task::where('status', 'done')->count();
        $this->inProgressTasks = Task::where('status', 'in_progress')->count();
    }

    public function render()
    {
        return view('modules/project::dashboard/task-stats-widget');
    }
}
```

**6.2 Widget View**:
```blade
<x-ui::card>
    <div class="card-header">
        <h3 class="card-title">{{ __('modules/project::dashboard.task_stats') }}</h3>
    </div>
    <div class="card-body">
        <div class="row g-3">
            {{-- Total Tasks --}}
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-primary">
                        {!! tabler_icon('checklist', ['class' => 'icon']) !!}
                    </div>
                    <div class="stat-info">
                        <div class="stat-value">{{ $totalTasks }}</div>
                        <div class="stat-label">{{ __('modules/project::dashboard.total') }}</div>
                    </div>
                </div>
            </div>

            {{-- In Progress --}}
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-warning">
                        {!! tabler_icon('hourglass', ['class' => 'icon']) !!}
                    </div>
                    <div class="stat-info">
                        <div class="stat-value">{{ $inProgressTasks }}</div>
                        <div class="stat-label">{{ __('modules/project::dashboard.in_progress') }}</div>
                    </div>
                </div>
            </div>

            {{-- Completed --}}
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-success">
                        {!! tabler_icon('check', ['class' => 'icon']) !!}
                    </div>
                    <div class="stat-info">
                        <div class="stat-value">{{ $completedTasks }}</div>
                        <div class="stat-label">{{ __('modules/project::dashboard.completed') }}</div>
                    </div>
                </div>
            </div>

            {{-- Overdue --}}
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-danger">
                        {!! tabler_icon('alert-circle', ['class' => 'icon']) !!}
                    </div>
                    <div class="stat-info">
                        <div class="stat-value">{{ $overdueTasks }}</div>
                        <div class="stat-label">{{ __('modules/project::dashboard.overdue') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-ui::card>
```

---

## Todo List

### Progress Calculation
- [ ] Create task_progress_audits table
- [ ] Create ProgressCalculationService
- [ ] Implement calculateFromSubtasks()
- [ ] Implement calculateFromEffort()
- [ ] Implement calculateFromStatus()
- [ ] Implement updateTaskProgress()
- [ ] Implement updateProjectProgress()

### Delay Detection
- [ ] Create DelayDetectionService
- [ ] Implement checkOverdueTasks()
- [ ] Implement getUpcomingDeadlines()
- [ ] Implement getStagnantTasks()
- [ ] Implement calculateScheduleVariance()

### Notifications
- [ ] Create NotificationService
- [ ] Create TaskOverdueNotification
- [ ] Create UpcomingDeadlineNotification
- [ ] Create DailyDigestNotification
- [ ] Create TaskEscalationNotification

### Scheduled Jobs
- [ ] Create CheckOverdueTasksJob
- [ ] Create SendDailyDigestJob
- [ ] Create CalculateProgressJob
- [ ] Schedule jobs in Kernel.php
- [ ] Test job execution

### Dashboard Widgets
- [ ] Create TaskStatsWidget
- [ ] Create ProgressChartWidget
- [ ] Create UpcomingDeadlinesWidget
- [ ] Add widgets to dashboard
- [ ] Test widget performance

---

## Success Criteria

### Progress Calculation
- [ ] Parent task progress = weighted avg of children
- [ ] Leaf task progress = actual/estimated * 100
- [ ] Progress updates trigger parent recalculation
- [ ] Audit trail records all changes
- [ ] Project progress = avg of root tasks

### Delay Detection
- [ ] Overdue tasks detected correctly
- [ ] Severity levels assigned (warning/critical/severe)
- [ ] Upcoming deadlines identified (7 days)
- [ ] Stagnant tasks detected (7+ days no progress)

### Notifications
- [ ] Instant notifications sent when task overdue
- [ ] Daily digest sent at 9 AM
- [ ] Escalation to PM after 3 days
- [ ] Escalation to stakeholders after 7 days
- [ ] Notifications appear in in-app notification center

### Dashboard
- [ ] Stats widgets load <2s
- [ ] Real-time updates (Livewire)
- [ ] Charts display correctly
- [ ] Mobile responsive

---

## Risk Assessment

| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| Recursive progress calculation infinite loop | High | Low | Max depth limit, cycle detection |
| Job queue performance issues | Medium | Medium | Queue on separate connection, rate limit |
| Notification spam (too many overdue) | Medium | High | Batch notifications, daily digest only |
| Dashboard widget performance | Low | Medium | Cache stats, update every 5 min |

---

## Security Considerations

1. **Authorization**: Check permissions before viewing stats
2. **Data Access**: Users only see their assigned tasks
3. **Notification Privacy**: Don't expose task details to unauthorized users
4. **Job Security**: Validate job inputs, prevent injection

---

## Next Steps

**Phase 06** (Permissions & Menu):
1. Complete permission flags
2. Verify menu integration
3. Finalize translations
4. Write user documentation
5. Prepare for production deployment

---

## Unresolved Questions

1. Should users be able to opt-out of daily digest emails?
2. How to handle tasks without due dates in notifications?
3. Should we support Slack/Teams integration?
4. What's the max recursion depth for progress calculation?

---

**Last Updated**: 2026-01-17
**Next Review**: After notification system tested
**Blocking**: Phase 01, Phase 02
