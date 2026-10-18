<?php

namespace Polirium\Modules\Product\Http\Model\Payment;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A cart open at the point of sale (one per invoice or return tab), so it
 * survives a reload, a closed browser or an expired session.
 */
class PosCart extends Model
{
    protected $table = 'product_pos_carts';

    protected $fillable = [
        'user_id',
        'branch_id',
        'tab_key',
        'type',
        'position',
        'state',
    ];

    protected $casts = [
        'state' => 'array',
    ];

    public function scopeOwnedBy(Builder $query, ?int $userId = null): Builder
    {
        return $query->where('user_id', $userId ?? (int) auth()->id());
    }
}
