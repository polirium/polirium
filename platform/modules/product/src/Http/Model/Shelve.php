<?php

namespace Polirium\Modules\Product\Http\Model;

use Polirium\Core\Base\Http\Models\BaseModel;

class Shelve extends BaseModel
{
    protected $table = 'shelves';

    protected $fillable = [
        'uuid',
        'name',
        'user_id',
    ];
}
