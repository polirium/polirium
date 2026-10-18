<?php

namespace Polirium\Modules\Product\Http\Model\Refund;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Polirium\Core\Base\Http\Models\BaseModel;
use Polirium\Modules\Product\Http\Model\Payment\Payment;
use Polirium\Modules\Product\Http\Model\Product;

class RefundProduct extends BaseModel
{
    protected $table = 'product_refund_products';

    protected $fillable = [
        'uuid',
        'product_payment_id',
        'product_refund_id',
        'product_id',
        'amount',                   // Số lượng sản phẩm refund
        'price',                    // Đơn giá
        'discount_value',           // Giá trị giảm giá
        'discount_type',            // Loại giảm giá (percent, number)
        'value',                    // Thành tiền
    ];

    protected $casts = [
        'product_payment_id' => 'integer',
        'product_refund_id' => 'integer',
        'product_id' => 'integer',
        'amount' => 'integer',
        'price' => 'integer',
        'discount_value' => 'integer',
        'value' => 'integer',
    ];

    /**
     * Get the product refund that owns the ProductRefundProduct
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function productRefund(): BelongsTo
    {
        return $this->belongsTo(Refund::class, 'product_refund_id');
    }

    /**
     * Lines of returns saved before money_unit existed hold price,
     * discount_value and value multiplied by 100.
     */
    protected function moneyScale(): int
    {
        return $this->productRefund?->legacyMoneyScale() ?? 1;
    }

    /**
     * Đơn giá (VNĐ)
     */
    public function getUnitPriceAttribute(): float
    {
        return ($this->price ?? 0) / $this->moneyScale();
    }

    /**
     * Giảm giá dòng (VNĐ hoặc %)
     */
    public function getLineDiscountAttribute(): float
    {
        return ($this->discount_value ?? 0) / $this->moneyScale();
    }

    /**
     * Thành tiền (VNĐ)
     */
    public function getLineTotalAttribute(): float
    {
        return ($this->value ?? 0) / $this->moneyScale();
    }

    /**
     * Get the product that owns the ProductRefundProduct
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get the payment that owns the ProductRefundProduct
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'product_payment_id');
    }
}
