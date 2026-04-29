<?php

namespace Polirium\Modules\Product\Http\Model\Stock;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Polirium\Core\Base\Http\Models\BaseModel;
use Polirium\Core\Base\Http\Models\Branch\Branch;
use Polirium\Core\Base\Http\Models\User;

class Stock extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'product_stocks';

    protected $fillable = [
        'uuid',
        'code',
        'branch_id',
        'balance_date',
        'amount',
        'increase_deviation',
        'decrease_deviation',
        'deviation',
        'value',
        'user_id',
        'user_created_id',
        'status',
        'note',
    ];

    public function products()
    {
        return $this->hasMany(StockProduct::class, 'stock_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function userCreated()
    {
        return $this->belongsTo(User::class, 'user_created_id');
    }

    public function getStatusNameAttribute(): string
    {
        $statuses = trans('modules/product::stock.status');

        return $statuses[$this->status] ?? $statuses['draft'];
    }
}
