<?php

namespace Polirium\Modules\Product\Http\Support;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
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
        ?int $branch_id = null,
        DateTimeInterface|string|null $logged_at = null
    ): void {
        $product = Product::select(['id'])->find($product_id);

        if (! $product) {
            return;
        }

        if (is_null($branch_id)) {
            $branch_id = user_branch() ?: 1; // Fallback to branch 1
        }

        DB::transaction(function () use (
            $product,
            $product_id,
            $productable_id,
            $productable_type,
            $amount,
            $value_before,
            $value_after,
            $increase,
            $branch_id,
            $logged_at
        ) {
            $after_amount = self::changeProductAmount($product, $amount, $increase, $branch_id);

            $logData = [
                'product_id' => $product_id,
                'branch_id' => $branch_id,
                'productable_id' => $productable_id,
                'productable_type' => $productable_type,
                'amount' => $amount,
                'direction' => $increase ? 'in' : 'out',
                'value_before' => $value_before,
                'value_after' => $value_after,
                'amount_before' => $after_amount['before'],
                'amount_after' => $after_amount['current'],
            ];

            if ($logged_at) {
                $logData['created_at'] = $logged_at;
                $logData['updated_at'] = $logged_at;
            }

            (new ProductLog())->forceFill($logData)->save();
        });
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

        return DB::transaction(function () use ($product, $amount, $increase, $branch_id) {
            $product_branch = ProductBranch::where('branch_id', $branch_id)
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->first();

            if (! $product_branch) {
                $product_branch = new ProductBranch();
                $product_branch->product_id = $product->id;
                $product_branch->branch_id = $branch_id;
                $product_branch->qty = 0;
                $product_branch->save();

                $product_branch = ProductBranch::where('id', $product_branch->id)
                    ->lockForUpdate()
                    ->first();
            }

            $previous_amount = (int) ($product_branch->qty ?: 0);
            $newQty = $increase
                ? $previous_amount + $amount
                : max(0, $previous_amount - $amount);

            $product_branch->qty = $newQty;
            $product_branch->save();

            return [
                'before' => $previous_amount,
                'current' => $newQty,
            ];
        });
    }
}
