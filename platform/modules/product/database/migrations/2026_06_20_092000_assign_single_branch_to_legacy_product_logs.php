<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $branchIds = DB::table('branches')->pluck('id');

        // Only safe when this installation has one warehouse/branch.
        if ($branchIds->count() !== 1) {
            return;
        }

        DB::table('product_logs')
            ->whereNull('branch_id')
            ->update(['branch_id' => $branchIds->first()]);
    }

    public function down(): void
    {
        // Do not erase a data correction automatically.
    }
};
