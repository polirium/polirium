<?php

namespace Polirium\Modules\Accounting\Http\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Polirium\Core\Base\Http\Models\BaseModel;
use Polirium\Core\Base\Http\Models\Branch\Branch;
use Polirium\Core\Base\Http\Models\User;
use Polirium\Modules\Product\Http\Model\Payment\PaymentProduct;

class Payment extends BaseModel
{
    protected $table = 'accouting_payments';

    protected $fillable = [
        'uuid',
        'branch_id',
        'code',
        'date',
        'type_id',
        'value',
        'user_id',
        'user_created_id',
        'finance_type',
        'finance_id',
        'business_result',
        'note',
    ];

    /**
     * Get the branch that owns the Receipt
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /**
     * Get the type that owns the Receipt
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(AccountingType::class, 'type_id');
    }

    public function finance()
    {
        return $this->morphTo('finance', 'finance_type', 'finance_id');
    }

    /**
     * Get the user that owns the Payment
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withDefault(['name' => null]);
    }

    /**
     * Get the userCreated that owns the Payment
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function userCreated(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_created_id')->withDefault(['name' => null]);
    }

    public function products(): HasMany
    {
        return $this->hasMany(PaymentProduct::class, 'product_payment_id');
    }
}
