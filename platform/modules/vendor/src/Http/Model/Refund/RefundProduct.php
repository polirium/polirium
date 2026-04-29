<?php

namespace Polirium\Modules\Vendor\Http\Model\Refund;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Polirium\Core\Base\Http\Models\BaseModel;
use Polirium\Modules\Product\Http\Model\Product;

class RefundProduct extends BaseModel
{
    protected $table = "vendor_purchase_refund_products";

    protected $fillable = [
        'uuid',
        'vendor_purchase_refund_id',
        'product_id',
        'amount',
        'price',
        'value',
        'note',
    ];

    /**
     * Get the product that owns the RefundProduct
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
