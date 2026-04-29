<?php

namespace Polirium\Modules\Accounting\Http\Model;

use Polirium\Core\Base\Http\Models\BaseModel;

class AccountingType extends BaseModel
{
    protected $table = 'accounting_types';

    protected $fillable = [
        'uuid',
        'name',
        'type',
        'note',
    ];
}
