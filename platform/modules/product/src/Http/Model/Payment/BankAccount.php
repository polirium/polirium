<?php

namespace Polirium\Modules\Product\Http\Model\Payment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankAccount extends Model
{
    protected $table = 'bank_accounts';

    protected $fillable = [
        'name',
        'account_number',
        'bank_code',
        'bank_name',
        'account_holder',
        'store_name',
        'template',
        'show_info',
        'full_account',
        'is_active',
        'is_default',
        'sort_order',
        'note',
    ];

    protected $casts = [
        'show_info' => 'boolean',
        'full_account' => 'boolean',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('ordered', function ($query) {
            $query->orderBy('sort_order')->orderBy('id');
        });
    }

    public function paymentMethods(): HasMany
    {
        return $this->hasMany(PaymentMethod::class, 'bank_account_id');
    }
}
