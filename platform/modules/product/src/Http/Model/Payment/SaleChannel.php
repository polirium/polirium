<?php

namespace Polirium\Modules\Product\Http\Model\Payment;

use Polirium\Core\Base\Http\Models\BaseModel;

class SaleChannel extends BaseModel
{
    protected $table = 'product_payment_sale_channels';

    protected static function booted(): void
    {
        static::addGlobalScope('ordered', function ($query) {
            $query->orderBy('sort_order')->orderBy('id');
        });
    }

    protected $fillable = [
        'uuid',
        'name',
        'description',
        'sort_order',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
