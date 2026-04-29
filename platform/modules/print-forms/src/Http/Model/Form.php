<?php

namespace Polirium\Modules\PrintForms\Http\Model;

use Illuminate\Support\Str;
use Polirium\Core\Base\Http\Models\BaseModel;

class Form extends BaseModel
{
    protected $table = 'forms';

    protected $fillable = [
        'uuid',
        'name',
        'content',
        'user_id',
        'type',
        'active',
        'paper_size',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }

            // Set default active state if not specified
            if (! isset($model->active)) {
                $model->active = 1;
            }
        });
    }
}
