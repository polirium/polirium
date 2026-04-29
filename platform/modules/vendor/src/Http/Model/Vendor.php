<?php

namespace Polirium\Modules\Vendor\Http\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Polirium\Core\Base\Http\Models\BaseModel;
use Polirium\Core\Base\Http\Models\Branch\Branch;

class Vendor extends BaseModel
{
    protected $table = "vendors";

    protected $fillable = [
        "uuid",
        'branch_id',
        'code',
        'name',
        'vat',
        'address',
        'phone',
        'email',
        "province_id",
        "district_id",
        "ward_id",
        "user_created_id",
        'company',
        'status',
        'total',
        'debt',
        'total_purchase',
        'note',
    ];

    /**
     * The groups that belong to the Vendor
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function group(): BelongsToMany
    {
        return $this->belongsToMany(VendorGroup::class, 'vendors_groups_pivot', 'vendor_id', 'vendor_group_id')
        ->withPivot(['id'])
        ->withTimestamps();
    }

    /**
     * Get the branch that owns the Vendor
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}
