<?php

namespace Polirium\Modules\Task\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Polirium\Core\Base\Http\Models\BaseModel;
use Polirium\Core\Base\Http\Models\User;

class TaskTimeLog extends BaseModel
{
    protected $table = 'task_time_logs';

    protected $fillable = [
        'task_id',
        'user_id',
        'hours',
        'log_date',
        'description',
    ];

    protected $casts = [
        'hours' => 'decimal:2',
        'log_date' => 'date',
    ];

    /**
     * Get the task that owns the time log.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get the user who logged the time.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withoutGlobalScope(\Illuminate\Database\Eloquent\SoftDeletingScope::class);
    }
}
