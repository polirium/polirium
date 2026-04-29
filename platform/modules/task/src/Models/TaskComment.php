<?php

namespace Polirium\Modules\Task\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Polirium\Core\Base\Http\Models\BaseModel;

class TaskComment extends BaseModel
{
    protected $table = 'task_comments';

    protected $fillable = [
        'task_id',
        'user_id',
        'content',
    ];

    /**
     * Get the task that owns the comment.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get the user who wrote the comment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\Polirium\Core\Base\Http\Models\User::class, 'user_id');
    }
}
