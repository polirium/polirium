<?php

namespace Polirium\Modules\Product\Http\Model\Payment;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Polirium\Core\Base\Http\Models\BaseModel;
use Polirium\Modules\Customer\Http\Model\Customer;

class Payment extends BaseModel
{
    protected $table = 'product_payments';

    protected $fillable = [
        'uuid',
        'branch_id',
        'customer_id',
        'code',
        'amount_products',      // Số lượng sản phẩm đơn hàng
        'total_cost',           // Tổng tiền hàng
        'discount_value',       // Giá trị giảm giá
        'discount_type',        // Giá trị giảm giá (["percent", "number"])
        'value',                // Số tiền khách cần trả
        'value_payment',        // Số tiền khách thanh toán
        'type_payment',         // Hình thức thanh toán (tm, thẻ, ngân hàng) (["cash", "bank", "card"])
        'is_delivery',          // Bán trực tiếp hay giao hàng
        'sale_channel_id',      // Kênh bán
        'is_cod',               // Giao hàng COD
        'user_id',              // Người bán
        'author_id',            // Người tạo đơn
        'status',
        'completed_at',
        'note',
    ];

    protected $casts = [
        'type_payment' => 'array',
        'completed_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($payment) {
            // Check if code is empty or matches the default auto-generated format (e.g. BH/00021)
            // If so, replace it with a temporary UUID to prevent race conditions during insert
            if (empty($payment->code) || preg_match('/^BH\/\d{5,}$/', $payment->code)) {
                $payment->code = 'TEMP-BH-' . (string) Str::uuid();
            }
        });

        static::created(function ($payment) {
            // After the DB assigns the auto-increment ID, we formulate the final sequential code
            if (str_starts_with($payment->code, 'TEMP-BH-')) {
                // Use max existing BH/ code number to ensure sequential numbering without gaps
                $maxCode = static::where('code', 'like', 'BH/%')
                    ->where('id', '!=', $payment->id)
                    ->selectRaw('MAX(CAST(SUBSTRING(code, 4) AS UNSIGNED)) as max_num')
                    ->value('max_num') ?? 0;

                $payment->code = code_generate('BH', $maxCode);
                $payment->saveQuietly();
            }
        });
    }

    /**
     * Get all of the products for the Payment
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function products(): HasMany
    {
        return $this->hasMany(PaymentProduct::class, 'product_payment_id');
    }

    /**
     * Get the customer that owns the Payment
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id')->withDefault(['name' => 'Khách lẻ']);
    }

    /**
     * Get the user (seller) that owns the Payment
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\Polirium\Core\Base\Http\Models\User::class, 'user_id');
    }

    /**
     * Get the author (creator) that owns the Payment
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(\Polirium\Core\Base\Http\Models\User::class, 'author_id');
    }

    /**
     * Get the branch that owns the Payment
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(\Polirium\Core\Base\Http\Models\Branch\Branch::class, 'branch_id');
    }

    /**
     * Get the sale channel that owns the Payment
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function saleChannel(): BelongsTo
    {
        return $this->belongsTo(SaleChannel::class, 'sale_channel_id');
    }

    /**
     * Get the refunds for the Payment
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function refunds(): HasMany
    {
        return $this->hasMany(\Polirium\Modules\Product\Http\Model\Refund\Refund::class, 'product_payment_id');
    }

    /**
     * Get the deliveries for the Payment
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(PaymentDelivery::class, 'product_payment_id');
    }

    /**
     * Get the latest delivery for the Payment
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function latestDelivery(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(PaymentDelivery::class, 'product_payment_id')->latestOfMany();
    }

}
