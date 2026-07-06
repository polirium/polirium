<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    public function up(): void
    {
        $product = DB::table('products')->where('code', 'XGF_000438.K')->first(['id', 'cost']);
        $stock = DB::table('product_stocks')->where('code', 'KK/00017')->first(['id', 'branch_id']);

        if (! $product || ! $stock) {
            return;
        }

        $stockProduct = DB::table('product_stock_products')
            ->where('stock_id', $stock->id)
            ->where('product_id', $product->id)
            ->whereNull('deleted_at')
            ->first(['id', 'amount', 'actual_stock']);

        if (! $stockProduct) {
            return;
        }

        $correctAmount = 10;
        $actualStock = (int) $stockProduct->actual_stock;
        $quantityDifference = $actualStock - $correctAmount;
        $valueDifference = $quantityDifference * (int) $product->cost;

        DB::table('product_stock_products')
            ->where('id', $stockProduct->id)
            ->update([
                'amount' => $correctAmount,
                'quantity_difference' => $quantityDifference,
                'value_difference' => $valueDifference,
                'updated_at' => now(),
            ]);

        $summary = DB::table('product_stock_products')
            ->where('stock_id', $stock->id)
            ->whereNull('deleted_at')
            ->selectRaw('
                COALESCE(SUM(actual_stock), 0) AS amount,
                COALESCE(SUM(CASE WHEN quantity_difference > 0 THEN quantity_difference ELSE 0 END), 0) AS increase_deviation,
                COALESCE(SUM(CASE WHEN quantity_difference < 0 THEN ABS(quantity_difference) ELSE 0 END), 0) AS decrease_deviation,
                COALESCE(SUM(quantity_difference), 0) AS deviation,
                COALESCE(SUM(value_difference), 0) AS value
            ')
            ->first();

        DB::table('product_stocks')
            ->where('id', $stock->id)
            ->update([
                'amount' => (int) $summary->amount,
                'increase_deviation' => (int) $summary->increase_deviation,
                'decrease_deviation' => (int) $summary->decrease_deviation,
                'deviation' => (int) $summary->deviation,
                'value' => (int) $summary->value,
                'updated_at' => now(),
            ]);

        DB::table('product_logs')
            ->where('product_id', $product->id)
            ->where('branch_id', $stock->branch_id)
            ->where('productable_type', 'Polirium\\Modules\\Product\\Http\\Model\\Stock\\Stock')
            ->where('productable_id', $stock->id)
            ->delete();
    }

    public function down(): void
    {
        $product = DB::table('products')->where('code', 'XGF_000438.K')->first(['id', 'cost']);
        $stock = DB::table('product_stocks')->where('code', 'KK/00017')->first(['id', 'branch_id', 'created_at', 'updated_at']);

        if (! $product || ! $stock) {
            return;
        }

        $stockProduct = DB::table('product_stock_products')
            ->where('stock_id', $stock->id)
            ->where('product_id', $product->id)
            ->whereNull('deleted_at')
            ->first(['id', 'actual_stock']);

        if (! $stockProduct) {
            return;
        }

        $legacyAmount = 0;
        $actualStock = (int) $stockProduct->actual_stock;
        $quantityDifference = $actualStock - $legacyAmount;
        $valueDifference = $quantityDifference * (int) $product->cost;

        DB::table('product_stock_products')
            ->where('id', $stockProduct->id)
            ->update([
                'amount' => $legacyAmount,
                'quantity_difference' => $quantityDifference,
                'value_difference' => $valueDifference,
                'updated_at' => now(),
            ]);

        DB::table('product_stocks')
            ->where('id', $stock->id)
            ->update([
                'amount' => $actualStock,
                'increase_deviation' => $quantityDifference > 0 ? $quantityDifference : 0,
                'decrease_deviation' => $quantityDifference < 0 ? abs($quantityDifference) : 0,
                'deviation' => $quantityDifference,
                'value' => $valueDifference,
                'updated_at' => now(),
            ]);

        DB::table('product_logs')->insert([
            'product_id' => $product->id,
            'branch_id' => $stock->branch_id,
            'productable_type' => 'Polirium\\Modules\\Product\\Http\\Model\\Stock\\Stock',
            'productable_id' => $stock->id,
            'amount' => $quantityDifference,
            'direction' => 'in',
            'value_before' => (int) $product->cost,
            'value_after' => $valueDifference,
            'amount_before' => 0,
            'amount_after' => $actualStock,
            'created_at' => $stock->created_at,
            'updated_at' => $stock->updated_at,
        ]);
    }
};
