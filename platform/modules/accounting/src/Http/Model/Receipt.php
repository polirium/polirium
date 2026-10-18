<?php

namespace Polirium\Modules\Accounting\Http\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Polirium\Core\Base\Http\Models\BaseModel;
use Polirium\Core\Base\Http\Models\Branch\Branch;
use Polirium\Core\Base\Http\Models\User;
use Polirium\Core\Base\Services\DocumentNumber;

class Receipt extends BaseModel
{
    protected $table = 'accounting_receipts';

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

    protected static function booted()
    {
        static::creating(function ($receipt) {
            // Suggested codes (PT/00012) are replaced by the next number from the
            // counter: computing max(id) + 1 gave two tills the same code.
            if (empty($receipt->code) || preg_match('/^PT\/\d{5,}$/', $receipt->code)) {
                $receipt->code = DocumentNumber::next('PT', $receipt->getTable());
            }
        });
    }

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
        return $this->belongsTo(User::class, 'user_id')
            ->withoutGlobalScope(\Illuminate\Database\Eloquent\SoftDeletingScope::class)
            ->withDefault(['name' => null]);
    }

    /**
     * Get the userCreated that owns the Payment
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function userCreated(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_created_id')
            ->withoutGlobalScope(\Illuminate\Database\Eloquent\SoftDeletingScope::class)
            ->withDefault(['name' => null]);
    }
}
