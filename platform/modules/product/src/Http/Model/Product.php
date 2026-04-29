<?php

namespace Polirium\Modules\Product\Http\Model;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Polirium\Core\Base\Http\Models\BaseModel;
use Polirium\Core\Base\Http\Models\Branch\Branch;
use Polirium\Modules\Product\Http\Model\Stock\StockProduct;

class Product extends BaseModel
{
    protected $table = 'products';

    protected $fillable = [
        'uuid',
        'name',
        'code',
        'category_id',
        'trademark_id',
        'shelve_id',
        'cost',
        'price',
        'qty',
        'weight',
        'weight_type',
        'allows_sale',
        'unit',
        'min_quantity',
        'max_quantity',
        'type',
        'description',
        'note',
        'user_id',
    ];

    private $recursive_parent = [];

    public function recursiveCategoryParent($category)
    {
        $this->recursive_parent[] = $category->name;

        if (! is_null($category->parent)) {
            $this->recursiveCategoryParent($category->parent);
        }
    }

    public function getCategoryParentAttribute()
    {
        $this->recursive_parent = [];
        if ($this->category_id) {
            $this->recursiveCategoryParent($this->category);
            $reverse = array_reverse($this->recursive_parent);

            return implode('>>', $reverse);
        }

        return $this->category?->name;
    }

    public function getNameUnitAttribute()
    {
        if (! empty($this->unit)) {
            return "{$this->name} ({$this->unit})";
        }

        return $this->name;
    }

    public function getTypeNameAttribute()
    {
        return match ($this->type) {
            'service' => trans('modules/product::product.services'),
            'combo' => trans('Combo'),
            default => trans('modules/product::product.goods'),
        };
    }

    public function amount(): Attribute
    {
        return Attribute::make(
            get: function () {
                // Dịch vụ không giới hạn tồn kho
                if ($this->type === 'service') {
                    return PHP_INT_MAX;
                }

                return $this->branches?->where('id', user_branch())->sum('pivot.qty') ?: 0;
            }
        );
    }

    /**
     * Scope a query only product in branch
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeProductBranch($query)
    {
        // Super Admin (no branch) can see all products
        if (! user_branch()) {
            return $query;
        }

        // Regular users only see products in their branch
        return $query->whereHas('branches', function ($q) {
            $q->where('branch_id', user_branch());
        });
    }

    /**
     * Get the category that owns the Product
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id')->select(['id', 'name', 'parent_id'])->withDefault(['name' => null]);
    }

    /**
     * Get the trademark that owns the Product
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function trademark(): BelongsTo
    {
        return $this->belongsTo(Trademark::class, 'trademark_id')->select(['id', 'name'])->withDefault(['name' => null]);
    }

    /**
     * Get the shelve that owns the Product
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function shelve(): BelongsTo
    {
        return $this->belongsTo(Shelve::class, 'shelve_id')->select(['id', 'name'])->withDefault(['name' => null]);
    }

    /**
     * Get all of the units for the Product
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function units(): HasMany
    {
        return $this->hasMany(ProductUnit::class, 'product_id');
    }

    /**
     * The branches that belong to the Product
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'product_branches', 'product_id', 'branch_id')
        ->withTimestamps()
        ->withPivot(['id', 'qty']);
    }

    /**
     * Get all of the logs for the Product
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function logs(): HasMany
    {
        return $this->hasMany(ProductLog::class, 'product_id')->orderByDesc('created_at')->orderByDesc('id');
    }

    public function elements()
    {
        return $this->hasMany(ProductElement::class, 'product_id');
    }

    public function stockProducts()
    {
        return $this->hasMany(StockProduct::class, 'product_id');
    }
}
