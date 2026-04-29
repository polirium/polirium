<?php

namespace Polirium\Modules\Product\Http\Model\Payment;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Polirium\Core\Base\Http\Models\BaseModel;
use Polirium\Modules\Product\Http\Model\Product;

class PaymentProduct extends BaseModel
{
    protected $table = 'product_payment_products';

    protected $fillable = [
        'uuid',
        'product_payment_id',
        'product_id',
        'amount',
        'value',
        'discount_value',
        'discount_type',
        'total',
        'note',
    ];

    /**
     * Get the payment that owns the PaymentProduct
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'product_payment_id');
    }

    /**
     * Get the product that owns the PaymentProduct
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
