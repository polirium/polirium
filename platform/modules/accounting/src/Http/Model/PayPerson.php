<?php

namespace Polirium\Modules\Accounting\Http\Model;

use Polirium\Core\Base\Http\Models\BaseModel;

class PayPerson extends BaseModel
{
    protected $table = 'accounting_pay_persons';

    protected $fillable = [
        'uuid',
        'name',
        'address',
        'phone',
        'province_id',
        'district_id',
        'ward_id',
        'note',
    ];
}
