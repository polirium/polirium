<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    public function up(): void
    {
        DB::transaction(function (): void {
            $combos = DB::table('products')->where('type', 'combo')->get(['id', 'code']);

            foreach ($combos as $combo) {
                $code = $combo->code;

                if (str_starts_with($code, 'HH/')) {
                    $candidate = 'CB/' . substr($code, 3);

                    if (DB::table('products')->where('id', '!=', $combo->id)->where('code', $candidate)->exists()) {
                        $candidate = 'CB/' . sprintf('%05d', $combo->id);
                    }

                    $code = $candidate;
                }

                $cost = (int) DB::table('product_elements')
                    ->join('products as components', 'components.id', '=', 'product_elements.element_id')
                    ->where('product_elements.product_id', $combo->id)
                    ->selectRaw('COALESCE(SUM(product_elements.qty * components.cost), 0) AS total_cost')
                    ->value('total_cost');

                DB::table('product_elements')
                    ->join('products as components', 'components.id', '=', 'product_elements.element_id')
                    ->where('product_elements.product_id', $combo->id)
                    ->update(['product_elements.price' => DB::raw('components.cost')]);

                DB::table('products')->where('id', $combo->id)->update([
                    'code' => $code,
                    'cost' => $cost,
                    'qty' => 0,
                    'updated_at' => now(),
                ]);

                DB::table('product_branches')->where('product_id', $combo->id)->update([
                    'qty' => 0,
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        // Combo code and derived inventory corrections cannot be safely reversed.
    }
};
