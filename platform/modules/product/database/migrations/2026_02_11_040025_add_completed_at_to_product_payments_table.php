<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_payments', function (Blueprint $table) {
            $table->timestamp('completed_at')->nullable()->after('status');
        });

        // Backfill: Đơn hàng đã hoàn thành (status = success) với phương thức thu tiền ngay
        // → completed_at = created_at
        DB::statement("
            UPDATE product_payments
            SET completed_at = created_at
            WHERE status = 'success'
              AND value_payment >= value
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_payments', function (Blueprint $table) {
            $table->dropColumn('completed_at');
        });
    }
};
