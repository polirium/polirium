<?php

namespace Polirium\Modules\Product\Http\Model;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Polirium\Core\Base\Http\Models\BaseModel;
use Polirium\Core\Base\Http\Models\Branch\Branch;
use Polirium\Modules\Product\Http\Model\Stock\StockProduct;
use Polirium\Modules\Product\Http\Support\ProductInventorySupport;

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

    /**
     * Tables whose rows keep referring to a product after it is used. Deleting
     * such a product orphans the stock card, invoices and receipts.
     */
    public const HISTORY_TABLES = [
        'product_logs' => 'product_id',
        'product_payment_products' => 'product_id',
        'product_payment_refund_products' => 'product_id',
        'product_refund_products' => 'product_id',
        'product_stock_products' => 'product_id',
        'product_elements' => 'element_id',
        'vendor_purchase_products' => 'product_id',
        'vendor_purchase_refund_products' => 'product_id',
        'vendor_transfer_products' => 'product_id',
    ];

    /**
     * Copy a product's definition only: a fresh uuid and code, no stock and no
     * history. Combo components and units are copied so a combo copy can sell.
     */
    public function duplicate(): self
    {
        return \Illuminate\Support\Facades\DB::transaction(function () {
            $copy = $this->replicate(['uuid', 'qty']);
            $copy->uuid = (string) \Illuminate\Support\Str::orderedUuid();
            $copy->code = self::makeCopyCode((string) $this->code);
            $copy->qty = 0;
            $copy->save();

            foreach (ProductBranch::where('product_id', $this->id)->pluck('branch_id')->unique() as $branchId) {
                ProductBranch::create(['product_id' => $copy->id, 'branch_id' => $branchId, 'qty' => 0]);
            }

            foreach (ProductElement::where('product_id', $this->id)->get() as $element) {
                $clone = $element->replicate(['uuid']);
                $clone->product_id = $copy->id;
                $clone->save();
            }

            foreach ($this->units()->get() as $unit) {
                $clone = $unit->replicate(['uuid']);
                $clone->product_id = $copy->id;
                $clone->save();
            }

            return $copy;
        });
    }

    public static function makeCopyCode(string $code): string
    {
        $baseCode = trim((string) preg_replace('/\(copy(?:\s+\d+)?\)$/i', '', trim($code)));

        for ($index = 1; $index <= 1000; $index++) {
            $suffix = $index === 1 ? '(copy)' : "(copy {$index})";
            $candidate = \Illuminate\Support\Str::limit($baseCode, 255 - strlen($suffix), '') . $suffix;

            if (! self::where('code', $candidate)->exists()) {
                return $candidate;
            }
        }

        return \Illuminate\Support\Str::limit($baseCode, 219, '') . '(copy ' . \Illuminate\Support\Str::random(30) . ')';
    }

    protected static function booted(): void
    {
        static::updating(function (Product $product) {
            // products.qty mirrors branch balances; stock only moves through the stock card.
            if ($product->isDirty('qty')) {
                $product->qty = $product->getOriginal('qty');
            }

            // Changing goods into a service/combo (or back) strands its stock and history.
            if ($product->isDirty('type') && $product->hasInventoryHistory()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'product' => "Hàng hóa {$product->code} đã có tồn kho hoặc chứng từ nên không thể đổi loại hàng. Hãy tạo mã mới.",
                ]);
            }
        });

        static::deleting(function (Product $product) {
            if ($product->hasInventoryHistory()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'product' => "Hàng hóa {$product->code} đã có tồn kho hoặc chứng từ nên không thể xóa. Hãy tắt \"Cho phép bán\" để ngừng kinh doanh.",
                ]);
            }

            \Illuminate\Support\Facades\DB::table('product_branches')->where('product_id', $product->id)->delete();
            \Illuminate\Support\Facades\DB::table('product_elements')->where('product_id', $product->id)->delete();
        });
    }

    public function hasInventoryHistory(): bool
    {
        if (\Illuminate\Support\Facades\DB::table('product_branches')->where('product_id', $this->id)->where('qty', '!=', 0)->exists()) {
            return true;
        }

        foreach (self::HISTORY_TABLES as $table => $column) {
            if (\Illuminate\Support\Facades\Schema::hasTable($table)
                && \Illuminate\Support\Facades\DB::table($table)->where($column, $this->id)->exists()) {
                return true;
            }
        }

        return false;
    }

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

                if ($this->type === 'combo') {
                    return ProductInventorySupport::availableQuantity($this, user_branch());
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
