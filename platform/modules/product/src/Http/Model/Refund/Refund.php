<?php

namespace Polirium\Modules\Product\Http\Model\Refund;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Polirium\Core\Base\Http\Models\BaseModel;
use Polirium\Core\Base\Http\Models\User;
use Polirium\Core\Base\Services\DocumentNumber;
use Polirium\Modules\Product\Http\Model\Payment\Payment;

class Refund extends BaseModel
{
    protected $table = 'product_refunds';

    protected $fillable = [
        'uuid',
        'checkout_key',
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
        'money_unit',               // 'vnd'; NULL = phiếu cũ (một số cột nhân 100)
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

    protected static function booted()
    {
        static::creating(function ($refund) {
            // Suggested codes (TRH/00004) are replaced by the next number from the counter.
            if (empty($refund->code) || preg_match('/^TRH\/\d{5,}$/', $refund->code)) {
                $refund->code = DocumentNumber::next('TRH', $refund->getTable());
            }
        });
    }

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
        return $this->hasMany(RefundProduct::class, 'product_refund_id')->chaperone('productRefund');
    }

    /**
     * Get the user that created the refund
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_created_id')->withoutGlobalScope(\Illuminate\Database\Eloquent\SoftDeletingScope::class);
    }

    /**
     * Get the user that processed the refund
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withoutGlobalScope(\Illuminate\Database\Eloquent\SoftDeletingScope::class);
    }

    public const MONEY_UNIT_VND = 'vnd';

    /**
     * Divisor for the amounts this return stored as x100: returns saved
     * before money_unit existed kept total_payable_amount, value and the
     * line price / discount_value / value multiplied by 100.
     * original_total_amount, discount_value and refund_fee were always VND.
     */
    public function legacyMoneyScale(): int
    {
        return $this->money_unit === self::MONEY_UNIT_VND ? 1 : 100;
    }

    /**
     * Tổng tiền gốc từ payment (VNĐ)
     *
     * @return float
     */
    public function getOriginalTotalAttribute(): float
    {
        return (float) ($this->original_total_amount ?? 0);
    }

    /**
     * Tổng sau giảm SP - Tổng tiền refund sản phẩm sau giảm giá từng SP (VNĐ)
     *
     * @return float
     */
    public function getTotalAfterProductDiscountAttribute(): float
    {
        return ($this->total_payable_amount ?? 0) / $this->legacyMoneyScale();
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

        if ($this->refund_fee_type === 'percent') {
            return $this->total_after_refund_discount * $this->refund_fee / 100;
        }

        return (float) $this->refund_fee;
    }

    /**
     * Tổng tiền trả cuối cùng (VNĐ)
     *
     * @return float
     */
    public function getFinalTotalAttribute(): float
    {
        return ($this->value ?? 0) / $this->legacyMoneyScale();
    }

    /**
     * Giảm giá refund (VNĐ hoặc %)
     *
     * @return float
     */
    public function getRefundDiscountAttribute(): float
    {
        return (float) ($this->discount_value ?? 0);
    }

    /**
     * Phí trả hàng (VNĐ hoặc %)
     *
     * @return float
     */
    public function getRefundFeeAmountAttribute(): float
    {
        return (float) ($this->refund_fee ?? 0);
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
