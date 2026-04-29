<?php

namespace Polirium\Modules\Accounting\Http\Model\Refund;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Polirium\Core\Base\Http\Models\BaseModel;
use Polirium\Modules\Product\Http\Model\Product;

class RefundProduct extends BaseModel
{
    protected $table = 'product_payment_refund_products';

    protected $fillable = [
        'uuid',
        'product_payment_refund_id',
        'product_id',
        'amount',
        'price',
        'value',
        'note',
        'product_payment_id',
    ];

    /**
     * Get the refund that owns the RefundProduct
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function refund(): BelongsTo
    {
        return $this->belongsTo(Refund::class, 'product_payment_refund_id');
    }

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
