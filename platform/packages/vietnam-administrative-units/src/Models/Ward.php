<?php

declare(strict_types=1);

namespace Polirium\VietnamAdministrativeUnits\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ward extends Model
{
    protected $table = 'wards';

    protected $fillable = ['name', 'gso_id', 'type', 'published', 'province_id', 'district_id'];

    protected $casts = ['published' => 'boolean'];

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'province_id');
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class, 'district_id');
    }
}
