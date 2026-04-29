<?php

namespace Polirium\Modules\Vendor\Http\Model\Purchase;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Polirium\Core\Base\Http\Models\BaseModel;
use Polirium\Core\Base\Http\Models\Branch\Branch;
use Polirium\Core\Base\Http\Models\User;
use Polirium\Modules\Vendor\Http\Model\Refund\Refund;
use Polirium\Modules\Vendor\Http\Model\Vendor;

class Purchase extends BaseModel
{
    protected $table = "vendor_purchases";

    protected $fillable = [
        'uuid',
        'code',
        'branch_id',
        'vendor_id',
        'discount_value',
        'discount_type',
        'user_created_id',
        'status',
        'total',
        'need_pay',
        'value',
        'note',
    ];

    public function url(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => route('vendors.purchases.show', ['id' => $this->id ?: null])
        );
    }

    public function urlHtml(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => '<a href="' . $this->url . '">' . $this->code . '</a>'
        );
    }

    /**
     * Get the branch that owns the Purchase
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /**
     * Get all of the products for the Purchase
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function products(): HasMany
    {
        return $this->hasMany(PurchaseProduct::class, 'vendor_purchase_id');
    }

    /**
     * Get the vendor that owns the Purchase
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    /**
     * Get the user that owns the Purchase
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function userCreated(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_created_id');
    }

    /**
     * Get all of the refunds for the Purchase
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class, 'purchase_id');
    }
}
