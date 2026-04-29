<?php

namespace Polirium\Modules\Product\Http\Model\Stock;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Polirium\Core\Base\Http\Models\BaseModel;
use Polirium\Modules\Product\Http\Model\Product;

class StockProduct extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'product_stock_products';

    protected $fillable = [
        'uuid',
        'stock_id',
        'product_id',
        'amount',
        'actual_stock',
        'quantity_difference',
        'value',
        'value_difference',
        'note',
    ];

    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
