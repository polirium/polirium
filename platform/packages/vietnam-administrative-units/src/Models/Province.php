<?php

declare(strict_types=1);

namespace Polirium\VietnamAdministrativeUnits\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Province extends Model
{
    protected $table = 'provinces';

    protected $fillable = ['name', 'gso_id', 'published'];

    protected $casts = ['published' => 'boolean'];

    public function districts(): HasMany
    {
        return $this->hasMany(District::class, 'province_id');
    }

    public function wards(): HasMany
    {
        return $this->hasMany(Ward::class, 'province_id');
    }
}
