<?php

namespace Polirium\Modules\Product\Http\Model;

use Polirium\Core\Base\Http\Models\BaseModel;

class ProductElement extends BaseModel
{
    protected $table = 'product_elements';

    protected $fillable = [
        'uuid',
        'product_id',
        'element_id',
        'qty',
        'price',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function element()
    {
        return $this->belongsTo(Product::class, 'element_id');
    }
}
