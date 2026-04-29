<?php

namespace Polirium\Modules\Task\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Polirium\Core\Base\Http\Models\BaseModel;

class TaskAttachment extends BaseModel
{
    protected $table = 'task_attachments';

    protected $fillable = [
        'task_id',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'uploaded_by',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    /**
     * Get the task that owns the attachment.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get the user who uploaded the attachment.
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(\Polirium\Core\Base\Http\Models\User::class, 'uploaded_by');
    }

    /**
     * Get file size in human readable format.
     */
    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
