<?php

namespace Polirium\Modules\Product\Http\Model\Payment;

use Polirium\Core\Base\Http\Models\BaseModel;

class PaymentPartnerDelivery extends BaseModel
{
    protected $table = 'product_payment_partner_deliveries';

    protected static function booted(): void
    {
        static::addGlobalScope('ordered', function ($query) {
            $query->orderBy('sort_order')->orderBy('id');
        });
    }

    protected $fillable = [
        'uuid',
        'type', // person, company
        'name',
        'code',
        'address',
        'phone',
        'email',
        'province_id',
        'district_id',
        'ward_id',
        'note',
        'sort_order',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];
}
