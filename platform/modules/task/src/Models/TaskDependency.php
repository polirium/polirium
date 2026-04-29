<?php

namespace Polirium\Modules\Task\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Polirium\Core\Base\Http\Models\BaseModel;

class TaskDependency extends BaseModel
{
    protected $table = 'task_dependencies';

    protected $fillable = [
        'predecessor_id',
        'successor_id',
        'dependency_type',
        'lag_days',
    ];

    protected $casts = [
        'lag_days' => 'integer',
    ];

    /**
     * Get the predecessor task.
     */
    public function predecessor(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'predecessor_id');
    }

    /**
     * Get the successor task.
     */
    public function successor(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'successor_id');
    }

    /**
     * Get dependency type label.
     */
    public function getDependencyTypeLabelAttribute(): string
    {
        return match ($this->dependency_type) {
            'finish_to_start' => __('modules/task::dependency.finish_to_start'),
            'start_to_start' => __('modules/task::dependency.start_to_start'),
            'finish_to_finish' => __('modules/task::dependency.finish_to_finish'),
            'start_to_finish' => __('modules/task::dependency.start_to_finish'),
            default => $this->dependency_type,
        };
    }

    /**
     * Get short dependency type label.
     */
    public function getShortTypeLabelAttribute(): string
    {
        return match ($this->dependency_type) {
            'finish_to_start' => 'FS',
            'start_to_start' => 'SS',
            'finish_to_finish' => 'FF',
            'start_to_finish' => 'SF',
            default => 'FS',
        };
    }
}
