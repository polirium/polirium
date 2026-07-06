<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    public function up(): void
    {
        $product = DB::table('products')
            ->where('code', 'XGT.310371')
            ->first(['id']);

        if (! $product) {
            return;
        }

        DB::table('product_branches')
            ->where('product_id', $product->id)
            ->where('branch_id', 1)
            ->where('qty', 25)
            ->update([
                'qty' => 19,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        //
    }
};
