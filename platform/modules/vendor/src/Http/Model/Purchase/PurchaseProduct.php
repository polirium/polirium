<?php

namespace Polirium\Modules\Vendor\Http\Model\Purchase;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Polirium\Core\Base\Http\Models\BaseModel;
use Polirium\Core\Base\Http\Models\Branch\Branch;
use Polirium\Modules\Product\Http\Model\Product;

class PurchaseProduct extends BaseModel
{
    protected $table = "vendor_purchase_products";

    protected $fillable = [
        "uuid",
        'branch_id',
        'product_id',
        'vendor_purchase_id',
        'amount',
        'price',
        "discount_value",
        "discount_type",
        'value',
        'note',
    ];

    public function discount(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                return $this->discount_value . ' ' . (in_array($this->discount_type, ["%", "percent"]) ? '%' : "VNĐ");
            },
        );
    }

    /**
     * Get the product that owns the PurchaseProduct
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
