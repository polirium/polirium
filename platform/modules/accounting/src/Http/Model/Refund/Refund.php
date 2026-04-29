<?php

namespace Polirium\Modules\Accounting\Http\Model\Refund;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Polirium\Core\Base\Http\Models\BaseModel;
use Polirium\Core\Base\Http\Models\Branch\Branch;
use Polirium\Core\Base\Http\Models\User;
use Polirium\Modules\Customer\Http\Model\Customer;
use Polirium\Modules\Product\Http\Model\Payment\Payment;

class Refund extends BaseModel
{
    protected $table = 'product_payment_refunds';

    protected $fillable = [
        'uuid',
        'branch_id',
        'customer_id',
        'product_payment_id',
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
                    // Reuse vendor translations or add new ones to accounting if needed.
                    // For now, let's assume we might need to add keys or just reuse for speed if appropriate,
                    // but better to default to a sensible string or key.
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

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'product_payment_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
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
        return $this->hasMany(RefundProduct::class, 'product_payment_refund_id');
    }
}
