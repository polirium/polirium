<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Recalculate product_branches.qty from actual transaction tables
     * (purchases, sales, refunds) instead of potentially corrupted product_logs.
     */
    public function up(): void
    {
        // Get all products that have any transactions
        $productIds = collect()
            ->merge(DB::table('vendor_purchase_products')->distinct()->pluck('product_id'))
            ->merge(DB::table('product_payment_products')->distinct()->pluck('product_id'))
            ->unique()
            ->values();

        $fixed = 0;

        foreach ($productIds as $productId) {
            // Total imported from successful purchases
            $imported = (int) DB::table('vendor_purchase_products')
                ->join('vendor_purchases', 'vendor_purchases.id', '=', 'vendor_purchase_products.vendor_purchase_id')
                ->where('vendor_purchase_products.product_id', $productId)
                ->where('vendor_purchases.status', 'success')
                ->sum('vendor_purchase_products.amount');

            // Total sold from non-cancelled invoices
            $sold = (int) DB::table('product_payment_products')
                ->join('product_payments', 'product_payments.id', '=', 'product_payment_products.product_payment_id')
                ->where('product_payment_products.product_id', $productId)
                ->whereNotIn('product_payments.status', ['cancel', 'cancelled'])
                ->sum('product_payment_products.amount');

            // Total refunded
            $refunded = 0;
            if (Schema::hasTable('product_payment_refund_products')) {
                $refunded = (int) DB::table('product_payment_refund_products')
                    ->where('product_id', $productId)
                    ->sum('amount');
            }

            // Also account for transfers
            $transferIn = 0;
            $transferOut = 0;
            if (Schema::hasTable('vendor_transfer_products')) {
                $transferIn = (int) DB::table('vendor_transfer_products')
                    ->join('vendor_transfers', 'vendor_transfers.id', '=', 'vendor_transfer_products.vendor_transfer_id')
                    ->where('vendor_transfer_products.product_id', $productId)
                    ->where('vendor_transfers.status', 'success')
                    ->where('vendor_transfers.to_branch_id', 1)
                    ->sum('vendor_transfer_products.amount');

                $transferOut = (int) DB::table('vendor_transfer_products')
                    ->join('vendor_transfers', 'vendor_transfers.id', '=', 'vendor_transfer_products.vendor_transfer_id')
                    ->where('vendor_transfer_products.product_id', $productId)
                    ->where('vendor_transfers.status', 'success')
                    ->where('vendor_transfers.form_branch_id', 1)
                    ->sum('vendor_transfer_products.amount');
            }

            $expectedQty = $imported - $sold + $refunded + $transferIn - $transferOut;

            $branch = DB::table('product_branches')
                ->where('product_id', $productId)
                ->where('branch_id', 1)
                ->first();

            $currentQty = (int) ($branch?->qty ?? 0);

            if ($currentQty !== $expectedQty) {
                Log::info("[StockRecalc] Product {$productId}: current={$currentQty} -> expected={$expectedQty} (imported={$imported} sold={$sold} refunded={$refunded} transferIn={$transferIn} transferOut={$transferOut})");

                if ($branch) {
                    DB::table('product_branches')
                        ->where('id', $branch->id)
                        ->update(['qty' => $expectedQty]);
                } else {
                    DB::table('product_branches')->insert([
                        'product_id' => $productId,
                        'branch_id' => 1,
                        'qty' => $expectedQty,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $fixed++;
            }
        }

        Log::info("[StockRecalc] Done. Checked {$productIds->count()} products, fixed {$fixed} mismatches.");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot be reversed — stock recalculation is a one-time data fix.
    }
};
