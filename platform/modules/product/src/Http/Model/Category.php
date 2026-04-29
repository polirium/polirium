<?php

namespace Polirium\Modules\Product\Http\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Polirium\Core\Base\Http\Models\BaseModel;

class Category extends BaseModel
{
    protected $table = 'categories';

    protected $fillable = [
        'uuid',
        'name',
        'parent_id',
        'user_id',
    ];

    /**
     * Get the parent that owns the Category
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Get all of the childs for the Category
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function childs(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Get hierarchical list with indentation for dropdown
     *
     * @return array
     */
    public static function getHierarchicalList(): array
    {
        $categories = self::whereNull('parent_id')
            ->orWhere('parent_id', 0)
            ->with(['childs' => function ($query) {
                $query->with(['childs' => function ($q) {
                    $q->with('childs');
                }]);
            }])
            ->get();

        $result = [];
        self::buildHierarchy($categories, $result, 0);

        return $result;
    }

    /**
     * Get all descendant category IDs (including the parent itself)
     * Used for filtering products by parent category + all children
     *
     * @param int $categoryId
     * @return array
     */
    public static function getAllDescendantIds(int $categoryId): array
    {
        $ids = [$categoryId];

        $category = self::with(['childs' => function ($query) {
            $query->with(['childs' => function ($q) {
                $q->with('childs');
            }]);
        }])->find($categoryId);

        if ($category) {
            self::collectChildIds($category->childs, $ids);
        }

        return $ids;
    }

    /**
     * Recursively collect child IDs
     */
    protected static function collectChildIds($children, &$ids): void
    {
        if (! $children) {
            return;
        }

        foreach ($children as $child) {
            $ids[] = $child->id;
            if ($child->childs && $child->childs->count() > 0) {
                self::collectChildIds($child->childs, $ids);
            }
        }
    }

    /**
     * Recursively build hierarchy array
     */
    protected static function buildHierarchy($categories, &$result, $level): void
    {
        foreach ($categories as $category) {
            // Simple indentation with dashes
            $prefix = str_repeat('— ', $level);
            $result[$category->id] = $prefix . $category->name;

            if ($category->childs && $category->childs->count() > 0) {
                self::buildHierarchy($category->childs, $result, $level + 1);
            }
        }
    }
}
