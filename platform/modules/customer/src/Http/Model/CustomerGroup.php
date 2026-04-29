<?php

namespace Polirium\Modules\Customer\Http\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Polirium\Core\Base\Http\Models\BaseModel;
use Polirium\Core\Base\Http\Models\User;

class CustomerGroup extends BaseModel
{
    protected $table = 'customer_groups';

    protected $fillable = [
        'uuid',
        'name',
        'type',
        'note',
        'user_id',
    ];

    /**
     * The customerGroups that belong to the Customer
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function customerGroups(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'customers_pivot_groups', 'customer_group_id', 'customer_id')
        ->withTimestamps()
        ->withPivot(['id']);
    }

    /**
     * Get the user that owns the CustomerGroup
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
