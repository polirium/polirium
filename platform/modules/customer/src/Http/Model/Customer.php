<?php

namespace Polirium\Modules\Customer\Http\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Polirium\Core\Base\Http\Models\BaseModel;
use Polirium\Core\Base\Http\Models\Branch\Branch;
use Polirium\Core\Base\Http\Models\District;
use Polirium\Core\Base\Http\Models\Province;
use Polirium\Core\Base\Http\Models\User;
use Polirium\Core\Base\Http\Models\Ward;

class Customer extends BaseModel
{
    protected $table = 'customers';

    protected $fillable = [
        'uuid',
        'code',
        'name',
        'phone',
        'phone2',
        'birthday',
        'sex',
        'address',
        'province_id',
        'district_id',
        'ward_id',
        'type',
        'company',
        'vat',
        'email',
        'facebook',
        'note',
        'status',
        'user_id',
        'branch_id',
    ];

    public function getGenderAttribute()
    {
        return match ((int) $this->sex) {
            1 => trans('modules/customer::customer.female'),
            default => trans('modules/customer::customer.male'),
        };
    }

    public function getProvinceDistrictWardAttribute()
    {
        return "{$this->province->name} - {$this->district->name} - {$this->ward->name}";
    }

    /**
     * The customerGroups that belong to the Customer
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function customerGroups(): BelongsToMany
    {
        return $this->belongsToMany(CustomerGroup::class, 'customers_pivot_groups', 'customer_id', 'customer_group_id')
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

    /**
     * Get the branch that owns the Customer
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /**
     * Get the province that owns the Branch
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'province_id')->withDefault(['name' => null]);
    }

    /**
     * Get the district that owns the Branch
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class, 'district_id')->withDefault(['name' => null]);
    }

    /**
     * Get the ward that owns the Branch
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class, 'ward_id')->withDefault(['name' => null]);
    }
}
