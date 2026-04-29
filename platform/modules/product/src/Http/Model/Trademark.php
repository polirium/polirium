<?php

namespace Polirium\Modules\Product\Http\Model;

use Polirium\Core\Base\Http\Models\BaseModel;

class Trademark extends BaseModel
{
    protected $table = 'trademarks';

    protected $fillable = [
        'uuid',
        'name',
        'user_id',
    ];
}
