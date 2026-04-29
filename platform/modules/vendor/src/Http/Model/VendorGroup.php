<?php

namespace Polirium\Modules\Vendor\Http\Model;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Polirium\Core\Base\Http\Models\BaseModel;

class VendorGroup extends BaseModel
{
    protected $table = "vendor_groups";

    protected $fillable = [
        "uuid",
        'name',
        'note',
        "user_created_id",
    ];

    /**
     * The vendors that belong to the Vendor
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function vendors(): BelongsToMany
    {
        return $this->belongsToMany(Vendor::class, 'vendors_groups_pivot', 'vendor_group_id', 'vendor_id')
        ->withPivot(['id'])
        ->withTimestamps();
    }
}
