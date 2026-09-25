<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Repair running stock balances after a branch stock recalculation wrote a
 * stale quantity into the affected product logs.
 */
return new class extends Migration
{
    public function up(): void
    {
        $productIds = DB::table('product_logs')->distinct()->pluck('product_id');

        foreach ($productIds as $productId) {
            $logs = DB::table('product_logs')
                ->where('product_id', $productId)
                ->orderBy('branch_id')
                ->orderBy('created_at')
                ->orderBy('id')
                ->get(['id', 'branch_id', 'amount', 'direction', 'amount_before', 'amount_after']);

            $runningByBranch = [];

            foreach ($logs as $log) {
                $branchId = (int) ($log->branch_id ?: 1);
                $before = $runningByBranch[$branchId] ?? 0;
                $amount = abs((int) $log->amount);
                $after = $log->direction === 'in'
                    ? $before + $amount
                    : ($log->direction === 'out' ? max(0, $before - $amount) : (int) $log->amount_after);

                if ((int) $log->amount_before !== $before || (int) $log->amount_after !== $after) {
                    DB::table('product_logs')->where('id', $log->id)->update([
                        'amount_before' => $before,
                        'amount_after' => $after,
                    ]);
                }

                $runningByBranch[$branchId] = $after;
            }

            foreach ($runningByBranch as $branchId => $quantity) {
                DB::table('product_branches')
                    ->where('product_id', $productId)
                    ->where('branch_id', $branchId)
                    ->update(['qty' => $quantity, 'updated_at' => now()]);
            }

            DB::table('products')->where('id', $productId)->update([
                'qty' => DB::table('product_branches')->where('product_id', $productId)->sum('qty'),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Running balances cannot be safely reconstructed to their corrupted values.
    }
};
