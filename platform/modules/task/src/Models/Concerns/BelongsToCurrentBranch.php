<?php

namespace Polirium\Modules\Task\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Projects and tasks belong to a branch. Every read and write goes through the
 * user's current branch so an id from another branch is never reachable.
 */
trait BelongsToCurrentBranch
{
    public function scopeVisible(Builder $query): Builder
    {
        $branchId = user_branch();

        return $query->when($branchId, fn (Builder $q) => $q->where($this->getTable() . '.branch_id', $branchId));
    }

    public static function findVisibleOrFail(int|string $id): static
    {
        return static::query()->visible()->findOrFail($id);
    }

    /**
     * Next sequential code for a prefix (e.g. TSK-00012), unique even when rows were deleted.
     */
    protected static function nextCode(string $prefix): string
    {
        $last = static::query()
            ->where('code', 'like', $prefix . '-%')
            ->pluck('code')
            ->map(fn (string $code) => (int) substr($code, strlen($prefix) + 1))
            ->max() ?? 0;

        do {
            $code = $prefix . '-' . str_pad((string) ++$last, 5, '0', STR_PAD_LEFT);
        } while (static::query()->where('code', $code)->exists());

        return $code;
    }
}
