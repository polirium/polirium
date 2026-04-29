<?php

namespace Polirium\Modules\Task\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Polirium\Core\Base\Http\Models\BaseModel;
use Polirium\Core\Base\Http\Models\Branch\Branch;

class Project extends BaseModel
{
    protected $table = 'projects';

    protected $fillable = [
        'uuid',
        'code',
        'name',
        'description',
        'client_id',
        'status',
        'priority',
        'planned_start_date',
        'planned_end_date',
        'actual_start_date',
        'actual_end_date',
        'budget',
        'progress_percentage',
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
        // 'budget' => 'decimal:2',
        // 'progress_percentage' => 'decimal:2',
    ];

    /**
     * Get the tasks for the project.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class)->orderBy('sort_order');
    }

    /**
     * Get the branch that owns the project.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /**
     * Get the user who created the project.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\Polirium\Core\Base\Http\Models\User::class, 'created_by');
    }

    /**
     * Get the user who updated the project.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(\Polirium\Core\Base\Http\Models\User::class, 'updated_by');
    }

    /**
     * Get status label attribute.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'planning' => __('modules/task::status.planning'),
            'active' => __('modules/task::status.active'),
            'on_hold' => __('modules/task::status.on_hold'),
            'completed' => __('modules/task::status.completed'),
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
     * Scope to only include active projects.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['planning', 'active', 'on_hold']);
    }

    /**
     * Scope to only include completed projects.
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    /**
     * Check if project is overdue.
     */
    public function getIsOverdueAttribute(): bool
    {
        if (! $this->planned_end_date || in_array($this->status, ['completed', 'cancelled'])) {
            return false;
        }

        return now()->greaterThan($this->planned_end_date);
    }

    /**
     * Auto-generate code if not set.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($project) {
            if (empty($project->code)) {
                $project->code = 'PRJ-' . str_pad(static::max('id') + 1, 5, '0', STR_PAD_LEFT);
            }
        });
    }
}
