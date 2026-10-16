<?php

declare(strict_types=1);

namespace Polirium\VietnamAdministrativeUnits\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class District extends Model
{
    protected $table = 'districts';

    protected $fillable = ['name', 'gso_id', 'published', 'province_id'];

    protected $casts = ['published' => 'boolean'];

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'province_id');
    }

    public function wards(): HasMany
    {
        return $this->hasMany(Ward::class, 'district_id');
    }
}
