<?php

namespace Polirium\Modules\Product\Http\Model;

use Polirium\Core\Base\Http\Models\BaseModel;

class ProductBranch extends BaseModel
{
    protected $table = 'product_branches';

    protected $fillable = [
        'uuid',
        'product_id',
        'branch_id',
        'qty',
    ];
}
