<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    private const PAYMENT_TYPE = 'Polirium\\Modules\\Product\\Http\\Model\\Payment\\Payment';

    /**
     * Counts physically verified by the warehouse on 2026-08-11. These values
     * take precedence over the calculated correction because later stock
     * adjustments may already have compensated for an invalid restock.
     */
    private const VERIFIED_QUANTITIES = [
        1 => [
            'XG.534252' => 0,
            'XG.741100' => 81,
            'XG.106407' => 80,
            'XG.106421' => 54,
        ],
    ];

    public function up(): void
    {
        DB::transaction(function (): void {
            $corrections = [];

            foreach ($this->findExcessCancelledRestocks() as $anomaly) {
                $excess = (int) $anomaly->quantity_in - (int) $anomaly->quantity_out;

                if ($excess <= 0) {
                    continue;
                }

                $removed = $this->removeNewestInboundQuantity(
                    (int) $anomaly->payment_id,
                    (int) $anomaly->product_id,
                    (int) $anomaly->branch_id,
                    $excess,
                );

                if ($removed === 0) {
                    continue;
                }

                $key = $anomaly->product_id.':'.$anomaly->branch_id;
                $corrections[$key] = ($corrections[$key] ?? 0) + $removed;
            }

            foreach ($corrections as $key => $quantity) {
                [$productId, $branchId] = array_map('intval', explode(':', $key));
                $product = DB::table('products')->where('id', $productId)->first(['code', 'type']);

                if (! $product || $product->type === 'service') {
                    continue;
                }

                $currentQuantity = (int) DB::table('product_branches')
                    ->where('product_id', $productId)
                    ->where('branch_id', $branchId)
                    ->value('qty');

                $correctedQuantity = self::VERIFIED_QUANTITIES[$branchId][$product->code]
                    ?? max(0, $currentQuantity - $quantity);

                $this->setBranchQuantity($productId, $branchId, $correctedQuantity);
                $this->rebuildStockCardBackward($productId, $branchId, $correctedQuantity);
            }
        });
    }

    /**
     * Find every cancelled payment whose inbound inventory logs exceed its
     * outbound logs. This intentionally scans the whole database rather than a
     * known list of product codes.
     */
    private function findExcessCancelledRestocks()
    {
        $inbound = "CASE WHEN product_logs.direction = 'in' OR (product_logs.direction IS NULL AND product_logs.amount_after > product_logs.amount_before) THEN ABS(product_logs.amount) ELSE 0 END";
        $outbound = "CASE WHEN product_logs.direction = 'out' OR (product_logs.direction IS NULL AND product_logs.amount_after < product_logs.amount_before) THEN ABS(product_logs.amount) ELSE 0 END";

        return DB::table('product_logs')
            ->join('product_payments', 'product_payments.id', '=', 'product_logs.productable_id')
            ->where('product_logs.productable_type', self::PAYMENT_TYPE)
            ->whereIn('product_payments.status', ['cancel', 'cancelled', 'failed', 'delivery_failed'])
            ->selectRaw('product_payments.id AS payment_id, product_logs.product_id, COALESCE(product_logs.branch_id, product_payments.branch_id) AS branch_id')
            ->selectRaw("SUM($inbound) AS quantity_in, SUM($outbound) AS quantity_out")
            ->groupBy('product_payments.id', 'product_logs.product_id')
            ->groupByRaw('COALESCE(product_logs.branch_id, product_payments.branch_id)')
            ->havingRaw("SUM($inbound) > SUM($outbound)")
            ->get();
    }

    /**
     * Remove only the unmatched inbound quantity, newest first. A partial log
     * is reduced instead of deleting more stock movement than the anomaly.
     */
    private function removeNewestInboundQuantity(int $paymentId, int $productId, int $branchId, int $quantity): int
    {
        $logs = DB::table('product_logs')
            ->where('productable_type', self::PAYMENT_TYPE)
            ->where('productable_id', $paymentId)
            ->where('product_id', $productId)
            ->where(function ($query) use ($branchId): void {
                $query->where('branch_id', $branchId)->orWhereNull('branch_id');
            })
            ->where(function ($query): void {
                $query->where('direction', 'in')
                    ->orWhere(function ($legacy): void {
                        $legacy->whereNull('direction')->whereColumn('amount_after', '>', 'amount_before');
                    });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get(['id', 'amount']);

        $remaining = $quantity;

        foreach ($logs as $log) {
            if ($remaining <= 0) {
                break;
            }

            $amount = abs((int) $log->amount);

            if ($amount <= $remaining) {
                DB::table('product_logs')->where('id', $log->id)->delete();
                $remaining -= $amount;
            } else {
                DB::table('product_logs')->where('id', $log->id)->update([
                    'amount' => $amount - $remaining,
                    'updated_at' => now(),
                ]);
                $remaining = 0;
            }
        }

        return $quantity - $remaining;
    }

    private function setBranchQuantity(int $productId, int $branchId, int $quantity): void
    {
        $branchStock = DB::table('product_branches')
            ->where('product_id', $productId)
            ->where('branch_id', $branchId);

        if ($branchStock->exists()) {
            $branchStock->update(['qty' => $quantity, 'updated_at' => now()]);
        } else {
            DB::table('product_branches')->insert([
                'product_id' => $productId,
                'branch_id' => $branchId,
                'qty' => $quantity,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('products')->where('id', $productId)->update([
            'qty' => (int) DB::table('product_branches')->where('product_id', $productId)->sum('qty'),
            'updated_at' => now(),
        ]);
    }

    /**
     * Rebuild balances backwards from the corrected current quantity so the
     * stock card remains continuous while retaining each action's real time.
     */
    private function rebuildStockCardBackward(int $productId, int $branchId, int $currentQuantity): void
    {
        $logs = DB::table('product_logs')
            ->where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get(['id', 'amount', 'direction', 'amount_before', 'amount_after']);

        $running = $currentQuantity;

        foreach ($logs as $log) {
            $amount = abs((int) $log->amount);
            $delta = match ($log->direction) {
                'in' => $amount,
                'out' => -$amount,
                default => $log->amount_after >= $log->amount_before ? $amount : -$amount,
            };
            $before = $running - $delta;

            DB::table('product_logs')->where('id', $log->id)->update([
                'amount_before' => $before,
                'amount_after' => $running,
            ]);

            $running = $before;
        }
    }

    public function down(): void
    {
        // Inventory corrections based on physical counts cannot be safely reversed.
    }
};
