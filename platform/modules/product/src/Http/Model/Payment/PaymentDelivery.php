<?php

namespace Polirium\Modules\Product\Http\Model\Payment;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Polirium\Core\Base\Http\Models\BaseModel;

class PaymentDelivery extends BaseModel
{
    protected $table = 'product_payment_deliveries';

    protected $fillable = [
        'uuid',
        'product_payment_id',
        'code',
        'partner_delivery_id',
        'type', // (["normal", "fast", "day"])
        'value',
        'date',
        'status', // (["wait", "delivery", "success", "cancel"])
    ];

    public function partnerDelivery(): BelongsTo
    {
        return $this->belongsTo(PaymentPartnerDelivery::class, 'partner_delivery_id');
    }
}
