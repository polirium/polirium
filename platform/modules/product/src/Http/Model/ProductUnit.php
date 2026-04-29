<?php

namespace Polirium\Modules\Product\Http\Model;

use Polirium\Core\Base\Http\Models\BaseModel;

class ProductUnit extends BaseModel
{
    protected $table = 'product_units';

    protected $fillable = [
        'uuid',
        'product_id',
        'name',
        'code',
        'conversion_value',
        'price',
        'allows_sale',
    ];
}
