<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_payment_sale_channels', function (Blueprint $table) {
            $table->boolean('is_default')->default(false)->after('is_active');
        });

        Schema::table('product_payment_partner_deliveries', function (Blueprint $table) {
            $table->boolean('is_default')->default(false)->after('is_active');
        });

        \Illuminate\Support\Facades\DB::table('product_payment_sale_channels')
            ->where('name', 'LIKE', '%Tại cửa hàng%')
            ->update(['is_default' => true]);

        \Illuminate\Support\Facades\DB::table('product_payment_partner_deliveries')
            ->where('name', 'LIKE', '%Tại shop%')
            ->update(['is_default' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_payment_sale_channels', function (Blueprint $table) {
            $table->dropColumn('is_default');
        });

        Schema::table('product_payment_partner_deliveries', function (Blueprint $table) {
            $table->dropColumn('is_default');
        });
    }
};
