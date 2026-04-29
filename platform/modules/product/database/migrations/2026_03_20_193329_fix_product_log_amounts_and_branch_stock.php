<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Fix product_logs amount_before/amount_after values.
 *
 * Root cause: ProductSupport::changeProductAmount() called increment/decrement
 * on a new model instance instead of the existing one, so the returned 'current'
 * value was stale. Also, productLogs() used $product->amount with only
 * select(['id']), giving null/0 for amount_before.
 *
 * This migration recalculates the running stock balance in all product_logs
 * and fixes product_branches.qty where the stock doesn't match the expected total.
 */
return new class () extends Migration {
    public function up(): void
    {
        // Type mappings: which types INCREASE stock
        $increaseTypes = [
            'Polirium\\Modules\\Vendor\\Http\\Model\\Purchase\\Purchase',
        ];

        // Types that DECREASE stock
        $decreaseTypes = [
            'Polirium\\Modules\\Product\\Http\\Model\\Payment\\Payment',
            'Polirium\\Modules\\Vendor\\Http\\Model\\Refund\\Refund',
        ];

        // Get all products that have logs
        $productIds = DB::table('product_logs')
            ->distinct()
            ->pluck('product_id');

        foreach ($productIds as $productId) {
            $logs = DB::table('product_logs')
                ->where('product_id', $productId)
                ->orderBy('id')
                ->get(['id', 'amount', 'productable_type', 'amount_before', 'amount_after']);

            $running = 0;

            foreach ($logs as $log) {
                $before = $running;

                if (in_array($log->productable_type, $increaseTypes)) {
                    $running += abs($log->amount);
                } elseif (in_array($log->productable_type, $decreaseTypes)) {
                    $running -= abs($log->amount);
                } else {
                    // Stock check, transfer — determine direction from original data
                    if ($log->amount_after >= $log->amount_before && $log->amount_before >= 0) {
                        $running += abs($log->amount);
                    } else {
                        $running -= abs($log->amount);
                    }
                }

                // Only update if values are wrong
                if ((int) $log->amount_before !== $before || (int) $log->amount_after !== $running) {
                    DB::table('product_logs')
                        ->where('id', $log->id)
                        ->update([
                            'amount_before' => $before,
                            'amount_after' => $running,
                        ]);
                }
            }

            // Fix product_branches qty if it doesn't match the calculated total
            // Use the purchase's branch_id to determine which branch to fix
            $branchId = DB::table('product_logs')
                ->where('product_id', $productId)
                ->where('productable_type', $increaseTypes[0])
                ->join('vendor_purchases', 'vendor_purchases.id', '=', 'product_logs.productable_id')
                ->value('vendor_purchases.branch_id');

            if ($branchId) {
                $currentQty = DB::table('product_branches')
                    ->where('product_id', $productId)
                    ->where('branch_id', $branchId)
                    ->value('qty');

                if ((int) $currentQty !== $running) {
                    DB::table('product_branches')
                        ->where('product_id', $productId)
                        ->where('branch_id', $branchId)
                        ->update(['qty' => $running]);
                }
            }
        }
    }

    public function down(): void
    {
        // Cannot reverse — data was already corrupted before this migration
    }
};
