<?php

namespace Polirium\Modules\Task\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Polirium\Core\Base\Http\Models\BaseModel;
use Polirium\Core\Base\Http\Models\Branch\Branch;

class Task extends BaseModel
{
    protected $table = 'tasks';

    protected $fillable = [
        'uuid',
        'code',
        'project_id',
        'parent_id',
        'name',
        'description',
        'status',
        'priority',
        'assigned_to',
        'planned_start_date',
        'planned_end_date',
        'actual_start_date',
        'actual_end_date',
        'estimated_hours',
        'actual_hours',
        'progress_percentage',
        'sort_order',
        'branch_id',
        'created_by',
        'updated_by',
        'note',
    ];

    protected $casts = [
        'planned_start_date' => 'date',
        'planned_end_date' => 'date',
        'actual_start_date' => 'date',
        'actual_end_date' => 'date',
        // 'estimated_hours' => 'decimal:2',
        // 'actual_hours' => 'decimal:2',
        // 'progress_percentage' => 'decimal:2',
        'is_overdue' => 'boolean',
    ];

    /**
     * Get the project that owns the task.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the parent task.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_id');
    }

    /**
     * Get the children tasks.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * Get the branch that owns the task.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /**
     * Get the user assigned to the task.
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(\Polirium\Core\Base\Http\Models\User::class, 'assigned_to');
    }

    /**
     * Get the user who created the task.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\Polirium\Core\Base\Http\Models\User::class, 'created_by');
    }

    /**
     * Get the user who updated the task.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(\Polirium\Core\Base\Http\Models\User::class, 'updated_by');
    }

    /**
     * Get the dependencies where this task is the predecessor.
     */
    public function dependencies(): HasMany
    {
        return $this->hasMany(TaskDependency::class, 'predecessor_id');
    }

    /**
     * Get the dependencies where this task is the successor.
     */
    public function dependents(): HasMany
    {
        return $this->hasMany(TaskDependency::class, 'successor_id');
    }

    /**
     * Get all comments for the task.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class);
    }

    /**
     * Get all attachments for the task.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class);
    }

    /**
     * Get all time logs for the task.
     */
    public function timeLogs(): HasMany
    {
        return $this->hasMany(TaskTimeLog::class);
    }

    /**
     * Get status label attribute.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'backlog' => __('modules/task::status.backlog'),
            'todo' => __('modules/task::status.todo'),
            'in_progress' => __('modules/task::status.in_progress'),
            'review' => __('modules/task::status.review'),
            'done' => __('modules/task::status.done'),
            'cancelled' => __('modules/task::status.cancelled'),
            default => $this->status,
        };
    }

    /**
     * Get priority label attribute.
     */
    public function getPriorityLabelAttribute(): string
    {
        return match ($this->priority) {
            'low' => __('modules/task::priority.low'),
            'medium' => __('modules/task::priority.medium'),
            'high' => __('modules/task::priority.high'),
            'urgent' => __('modules/task::priority.urgent'),
            default => $this->priority,
        };
    }

    /**
     * Check if task is overdue.
     */
    public function getIsOverdueAttribute(): bool
    {
        if (! $this->planned_end_date || in_array($this->status, ['done', 'cancelled'])) {
            return false;
        }

        return now()->greaterThan($this->planned_end_date);
    }

    /**
     * Check if task is a leaf task (has no children).
     */
    public function getIsLeafAttribute(): bool
    {
        return $this->children()->count() === 0;
    }

    /**
     * Scope to only include root tasks.
     */
    public function scopeRoot(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope to only include active tasks.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['todo', 'in_progress', 'review']);
    }

    /**
     * Scope by status.
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope by priority.
     */
    public function scopeByPriority(Builder $query, string $priority): Builder
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope overdue tasks.
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereNotNull('planned_end_date')
            ->where('planned_end_date', '<', now())
            ->whereNotIn('status', ['done', 'cancelled']);
    }

    /**
     * Auto-generate code if not set.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($task) {
            if (empty($task->code)) {
                $prefix = 'TSK';
                $taskId = static::max('id') + 1;
                $task->code = $prefix . '-' . str_pad($taskId, 5, '0', STR_PAD_LEFT);
            }
        });
    }
}
