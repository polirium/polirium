<?php

namespace Polirium\Modules\Vendor\Http\Model\Transfer;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Polirium\Core\Base\Http\Models\BaseModel;
use Polirium\Core\Base\Http\Models\Branch\Branch;
use Polirium\Core\Base\Http\Models\User;

class Transfer extends BaseModel
{
    protected $table = "vendor_transfers";

    protected $fillable = [
        'uuid',
        'code',
        'status',
        'branch_id',
        'form_branch_id',
        'to_branch_id',
        'user_created_id',
        'date_send',
        'date_take',
        'note',
    ];

    public function getValueAttribute()
    {
        return $this->products()->sum("value");
    }

    public function getAmountAttribute()
    {
        return $this->products()->sum("amount");
    }

    public function statusName(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if ($this->status) {
                    return trans('modules/vendor::transfer.status.' . $this->status);
                }

                return trans('modules/vendor::transfer.status.temp');
            },
        );
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

    /**
     * Get the fromBranch that owns the Transfer
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function fromBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'form_branch_id');
    }

    /**
     * Get the toBranch that owns the Transfer
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function toBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'to_branch_id');
    }

    /**
     * Get all of the products for the Transfer
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function products(): HasMany
    {
        return $this->hasMany(TransferProduct::class, 'vendor_transfer_id');
    }

    /**
     * Get the userCreated that owns the Transfer
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function userCreated(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_created_id');
    }
}
