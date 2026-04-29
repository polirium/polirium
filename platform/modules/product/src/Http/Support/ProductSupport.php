<?php

namespace Polirium\Modules\Product\Http\Support;

use Illuminate\Database\Eloquent\Model;
use Polirium\Modules\Product\Http\Model\Product;
use Polirium\Modules\Product\Http\Model\ProductBranch;
use Polirium\Modules\Product\Http\Model\ProductLog;

class ProductSupport
{
    public function productLogs(
        int $product_id,
        int $productable_id,
        Model|string|null $productable_type,
        int $amount = 0,
        int $value_before = 0,
        int $value_after = 0,
        bool $increase = true,
        ?int $branch_id = null
    ): void {
        $product = Product::select(['id'])->find($product_id);

        if (! $product) {
            return;
        }

        if (is_null($branch_id)) {
            $branch_id = user_branch() ?: 1; // Fallback to branch 1
        }

        $after_amount = self::changeProductAmount($product, $amount, $increase, $branch_id);

        ProductLog::create([
            'product_id' => $product_id,
            'productable_id' => $productable_id,
            'productable_type' => $productable_type,
            'amount' => $amount,
            'value_before' => $value_before,
            'value_after' => $value_after,
            'amount_before' => $after_amount['before'],
            'amount_after' => $after_amount['current'],
        ]);
    }

    public function changeProductAmount(int|Product $product, int $amount, bool $increase = true, ?int $branch_id = null): array
    {
        if (is_int($product)) {
            $product = Product::select(['id', 'type'])->find($product);
        }

        if (! $product) {
            return [
                'before' => 0,
                'current' => 0,
            ];
        }

        // Dịch vụ không quản lý tồn kho
        if ($product->type === 'service') {
            return [
                'before' => 0,
                'current' => 0,
            ];
        }

        if (is_null($branch_id)) {
            $branch_id = user_branch() ?: 1; // Fallback to branch 1
        }

        $product_branch = ProductBranch::where('branch_id', $branch_id)->where('product_id', $product->id)->first();

        if (! $product_branch) {
            $product_branch = new ProductBranch();
            $product_branch->product_id = $product->id;
            $product_branch->branch_id = $branch_id;
            $product_branch->qty = 0;
            $product_branch->save();

            $product_branch->refresh();
        }

        $previous_amount = $product_branch?->qty ?: 0;

        if ($increase) {
            $product_branch->increment('qty', $amount);
        } else {
            // Không cho tồn kho xuống dưới 0
            $newQty = $previous_amount - $amount;
            if ($newQty < 0) {
                $product_branch->update(['qty' => 0]);
            } else {
                $product_branch->decrement('qty', $amount);
            }
        }

        $product_branch->refresh();

        return [
            'before' => $previous_amount,
            'current' => $product_branch->qty,
        ];
    }
}
