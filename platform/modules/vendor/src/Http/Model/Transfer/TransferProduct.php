<?php

namespace Polirium\Modules\Vendor\Http\Model\Transfer;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Polirium\Core\Base\Http\Models\BaseModel;
use Polirium\Modules\Product\Http\Model\Product;

class TransferProduct extends BaseModel
{
    protected $table = "vendor_transfer_products";

    protected $fillable = [
        'uuid',
        'vendor_transfer_id',
        'product_id',
        'amount',
        'price',
        'value',
        'note',
    ];

    /**
     * Get the product that owns the TransferProduct
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
