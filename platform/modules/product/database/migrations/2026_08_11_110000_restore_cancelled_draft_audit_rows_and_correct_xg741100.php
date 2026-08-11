<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class () extends Migration {
    private const PAYMENT_TYPE = 'Polirium\\Modules\\Product\\Http\\Model\\Payment\\Payment';

    private const STOCK_TYPE = 'Polirium\\Modules\\Product\\Http\\Model\\Stock\\Stock';

    /**
     * Rows removed by 2026_08_11_090000. Restore them as zero-quantity audit
     * rows: the cancelled document remains visible without changing inventory.
     */
    private const CANCELLED_DRAFT_ROWS = [
        'BH/01614' => ['XG.003550', 'XG.901159', 'XG.901000', 'PKXG.HLX-CU01'],
        'HD/02596' => ['XG.106421', 'XG.106407', 'XG.741100', 'PVC'],
        'HD/02620' => ['PVC', 'PKXG.XJ050', 'PK_HQS.K', 'XG.534252', 'PK.TUIVAI'],
    ];

    public function up(): void
    {
        DB::transaction(function (): void {
            $this->restoreAuditRows();
            $this->correctVerifiedQuantity('XG.741100', 1, 79);
        });
    }

    private function restoreAuditRows(): void
    {
        foreach (self::CANCELLED_DRAFT_ROWS as $paymentCode => $productCodes) {
            $payment = DB::table('product_payments')->where('code', $paymentCode)
                ->first(['id', 'branch_id', 'updated_at']);

            if (! $payment) {
                continue;
            }

            foreach ($productCodes as $productCode) {
                $product = DB::table('products')->where('code', $productCode)->first(['id', 'cost']);

                if (! $product) {
                    continue;
                }

                $exists = DB::table('product_logs')
                    ->where('productable_type', self::PAYMENT_TYPE)
                    ->where('productable_id', $payment->id)
                    ->where('product_id', $product->id)
                    ->exists();

                if ($exists) {
                    continue;
                }

                $branchId = (int) ($payment->branch_id ?: 1);
                $quantity = (int) DB::table('product_branches')
                    ->where('product_id', $product->id)
                    ->where('branch_id', $branchId)
                    ->value('qty');
                $loggedAt = $payment->updated_at ?: now();
                $balanceAtCancellation = $this->balanceAt(
                    (int) $product->id,
                    $branchId,
                    $loggedAt,
                    $quantity,
                );

                DB::table('product_logs')->insert([
                    'uuid' => (string) Str::uuid(),
                    'product_id' => $product->id,
                    'branch_id' => $branchId,
                    'productable_id' => $payment->id,
                    'productable_type' => self::PAYMENT_TYPE,
                    'amount' => 0,
                    'direction' => 'in',
                    'value_before' => (int) $product->cost,
                    'value_after' => (int) $product->cost,
                    'amount_before' => $balanceAtCancellation,
                    'amount_after' => $balanceAtCancellation,
                    'created_at' => $loggedAt,
                    'updated_at' => $loggedAt,
                ]);
            }
        }
    }

    private function balanceAt(int $productId, int $branchId, $loggedAt, int $fallback): int
    {
        $previousBalance = DB::table('product_logs')
            ->where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->where('created_at', '<=', $loggedAt)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->value('amount_after');

        if ($previousBalance !== null) {
            return (int) $previousBalance;
        }

        $nextBalance = DB::table('product_logs')
            ->where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->where('created_at', '>', $loggedAt)
            ->orderBy('created_at')
            ->orderBy('id')
            ->value('amount_before');

        return $nextBalance !== null ? (int) $nextBalance : $fallback;
    }

    private function correctVerifiedQuantity(string $code, int $branchId, int $targetQuantity): void
    {
        $product = DB::table('products')->where('code', $code)->first(['id', 'cost']);

        if (! $product) {
            return;
        }

        $branchStock = DB::table('product_branches')
            ->where('product_id', $product->id)
            ->where('branch_id', $branchId)
            ->lockForUpdate();
        $currentQuantity = (int) $branchStock->value('qty');

        if ($currentQuantity === $targetQuantity) {
            return;
        }

        $branchStock->update(['qty' => $targetQuantity, 'updated_at' => now()]);
        DB::table('products')->where('id', $product->id)->update([
            'qty' => (int) DB::table('product_branches')->where('product_id', $product->id)->sum('qty'),
            'updated_at' => now(),
        ]);

        DB::table('product_logs')->insert([
            'uuid' => (string) Str::uuid(),
            'product_id' => $product->id,
            'branch_id' => $branchId,
            'productable_id' => 0,
            'productable_type' => self::STOCK_TYPE,
            'amount' => abs($targetQuantity - $currentQuantity),
            'direction' => $targetQuantity > $currentQuantity ? 'in' : 'out',
            'value_before' => (int) $product->cost,
            'value_after' => (int) $product->cost,
            'amount_before' => $currentQuantity,
            'amount_after' => $targetQuantity,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Audit restoration and a physically verified stock count are irreversible.
    }
};
