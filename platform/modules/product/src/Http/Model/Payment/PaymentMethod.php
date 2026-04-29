<?php

namespace Polirium\Modules\Product\Http\Model\Payment;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $table = 'payment_methods';

    protected static function booted(): void
    {
        static::addGlobalScope('ordered', function ($query) {
            $query->orderBy('sort_order')->orderBy('id');
        });
    }

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
        'is_default',
        'sort_order',
        'target_payment_status',
    ];

    public const STATUS_COMPLETED = 'completed';
    public const STATUS_PENDING = 'pending';

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];
}
