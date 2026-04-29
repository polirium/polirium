<?php

namespace Polirium\Modules\Product\Http\Model\Refund;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Polirium\Core\Base\Http\Models\BaseModel;
use Polirium\Modules\Product\Http\Model\Payment\Payment;

class Refund extends BaseModel
{
    protected $table = 'product_refunds';

    protected $fillable = [
        'uuid',
        'product_payment_id',
        'code',
        'discount_value',           // Giá trị giảm giá
        'discount_type',            // Loại giảm giá (percent, number)
        'refund_fee',               // Phí trả hàng
        'refund_fee_type',          // Loại phí trả hàng (percent, number)
        'amount',                   // Số lượng hàng trả
        'original_total_amount',    // Tổng tiền gốc hàng mua
        'total_payable_amount',     // Tổng tiền phải trả
        'value',                    // Tổng tiền trả
        'user_id',                  // Người thực hiện refund
        'user_created_id',          // Người tạo refund
        'note',                     // Ghi chú
    ];

    protected $casts = [
        'discount_value' => 'integer',
        'refund_fee' => 'integer',
        'amount' => 'integer',
        'original_total_amount' => 'integer',
        'total_payable_amount' => 'integer',
        'value' => 'integer',
        'user_id' => 'integer',
        'user_created_id' => 'integer',
    ];

    /**
     * Get the payment that owns the ProductRefund
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'product_payment_id');
    }

    /**
     * Get all of the refund products for the Refund
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function products(): HasMany
    {
        return $this->hasMany(RefundProduct::class, 'product_refund_id');
    }

    /**
     * Get the user that created the refund
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_created_id');
    }

    /**
     * Get the user that processed the refund
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    /**
     * Tổng tiền gốc từ payment (VNĐ)
     *
     * @return float
     */
    public function getOriginalTotalAttribute(): float
    {
        return ($this->original_total_amount ?? 0) / 100;
    }

    /**
     * Tổng sau giảm SP - Tổng tiền refund sản phẩm sau giảm giá từng SP (VNĐ)
     *
     * @return float
     */
    public function getTotalAfterProductDiscountAttribute(): float
    {
        return ($this->total_payable_amount ?? 0) / 100;
    }

    /**
     * Tổng phải trả - Sau khi áp dụng giảm giá refund (VNĐ)
     *
     * @return float
     */
    public function getTotalAfterRefundDiscountAttribute(): float
    {
        $total = $this->total_after_product_discount;

        return discount_value($total, ($this->discount_value ?? 0), $this->discount_type ?? 'number');
    }

    /**
     * Phí trả hàng tính toán (VNĐ)
     *
     * @return float
     */
    public function getCalculatedRefundFeeAttribute(): float
    {
        if (($this->refund_fee ?? 0) <= 0) {
            return 0;
        }

        $baseAmount = $this->total_after_refund_discount;

        if ($this->refund_fee_type === 'percent') {
            return ($baseAmount * ($this->refund_fee / 100)) / 100;
        } else {
            return ($this->refund_fee ?? 0) / 100;
        }
    }

    /**
     * Tổng tiền trả cuối cùng (VNĐ)
     *
     * @return float
     */
    public function getFinalTotalAttribute(): float
    {
        return ($this->value ?? 0) / 100;
    }

    /**
     * Giảm giá refund (VNĐ hoặc %)
     *
     * @return float
     */
    public function getRefundDiscountAttribute(): float
    {
        return ($this->discount_value ?? 0) / 100;
    }

    /**
     * Phí trả hàng (VNĐ hoặc %)
     *
     * @return float
     */
    public function getRefundFeeAmountAttribute(): float
    {
        return ($this->refund_fee ?? 0) / 100;
    }

    /**
     * Formatted tổng tiền gốc
     *
     * @return string
     */
    public function getFormattedOriginalTotalAttribute(): string
    {
        return core_number_format($this->original_total);
    }

    /**
     * Formatted tổng sau giảm SP
     *
     * @return string
     */
    public function getFormattedTotalAfterProductDiscountAttribute(): string
    {
        return core_number_format($this->total_after_product_discount);
    }

    /**
     * Formatted tổng phải trả
     *
     * @return string
     */
    public function getFormattedTotalAfterRefundDiscountAttribute(): string
    {
        return core_number_format($this->total_after_refund_discount);
    }

    /**
     * Formatted phí trả hàng
     *
     * @return string
     */
    public function getFormattedCalculatedRefundFeeAttribute(): string
    {
        return core_number_format($this->calculated_refund_fee);
    }

    /**
     * Formatted tổng tiền trả cuối cùng
     *
     * @return string
     */
    public function getFormattedFinalTotalAttribute(): string
    {
        return core_number_format($this->final_total);
    }

    /**
     * Check if refund has discount applied
     *
     * @return bool
     */
    public function hasDiscount(): bool
    {
        return $this->discount_value > 0;
    }

    /**
     * Check if refund has fee applied
     *
     * @return bool
     */
    public function hasFee(): bool
    {
        return $this->refund_fee > 0;
    }
}
