<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('product_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->nullable()->after('product_id')->index();
            $table->string('direction', 3)->nullable()->after('amount')->index();
            $table->index(['branch_id', 'product_id', 'created_at'], 'product_logs_report_index');
        });

        // Backfill branch and direction from documents where the source is unambiguous.
        DB::statement("UPDATE product_logs l JOIN product_payments p ON p.id = l.productable_id SET l.branch_id = p.branch_id WHERE l.branch_id IS NULL AND l.productable_type = 'Polirium\\Modules\\Product\\Http\\Model\\Payment\\Payment'");
        DB::statement("UPDATE product_logs l JOIN vendor_purchases p ON p.id = l.productable_id SET l.branch_id = p.branch_id WHERE l.branch_id IS NULL AND l.productable_type = 'Polirium\\Modules\\Vendor\\Http\\Model\\Purchase\\Purchase'");
        DB::statement("UPDATE product_logs l JOIN vendor_transfers t ON t.id = l.productable_id SET l.branch_id = t.form_branch_id WHERE l.branch_id IS NULL AND l.productable_type = 'Polirium\\Modules\\Vendor\\Http\\Model\\Transfer\\Transfer'");
        DB::table('product_logs')->whereNull('direction')->update([
            'direction' => DB::raw("CASE WHEN amount_after >= amount_before THEN 'in' ELSE 'out' END"),
        ]);
    }

    public function down(): void
    {
        Schema::table('product_logs', function (Blueprint $table) {
            $table->dropIndex('product_logs_report_index');
            $table->dropColumn(['branch_id', 'direction']);
        });
    }
};
