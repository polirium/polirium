<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Renumber existing BH/ invoice codes to be sequential without gaps.
 *
 * Before: BH/00001, BH/00002, ..., BH/00010, BH/00063, BH/00073, ...
 * After:  BH/00001, BH/00002, ..., BH/00010, BH/00011, BH/00012, ...
 */
return new class () extends Migration {
    public function up(): void
    {
        $payments = DB::table('product_payments')
            ->where('code', 'like', 'BH/%')
            ->orderBy('id')
            ->get(['id', 'code']);

        $counter = 1;

        foreach ($payments as $payment) {
            $newCode = 'BH/' . str_pad($counter, 5, '0', STR_PAD_LEFT);

            if ($payment->code !== $newCode) {
                DB::table('product_payments')
                    ->where('id', $payment->id)
                    ->update(['code' => $newCode]);
            }

            $counter++;
        }
    }

    public function down(): void
    {
        // Cannot reverse — original gaps were from deleted records
    }
};
