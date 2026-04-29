<?php

namespace Polirium\Modules\Vendor\Http\Model\Refund;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Polirium\Core\Base\Http\Models\BaseModel;
use Polirium\Core\Base\Http\Models\Branch\Branch;
use Polirium\Core\Base\Http\Models\User;
use Polirium\Modules\Vendor\Http\Model\Purchase\Purchase;
use Polirium\Modules\Vendor\Http\Model\Vendor;

class Refund extends BaseModel
{
    protected $table = "vendor_purchase_refunds";

    protected $fillable = [
        "uuid",
        'branch_id',
        'vendor_id',
        'purchase_id',
        'code',
        'total',
        'discount_value',
        'discount_type',
        'value',
        'note',
        'status',
        'user_created_id',
    ];

    public function statusName(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (empty($this->status)) {
                    return trans('modules/vendor::purchase.refund.status.temp');
                }

                return trans('modules/vendor::purchase.refund.status.' . $this->status);
            },
        );
    }

    public function userCreated(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_created_id');
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class, 'purchase_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /**
     * Get all of the products for the Refund
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function products(): HasMany
    {
        return $this->hasMany(RefundProduct::class, 'vendor_purchase_refund_id');
    }
}
