<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('product_payment_sale_channels', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('sort_order');
        });

        Schema::table('product_payment_partner_deliveries', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('product_payment_sale_channels', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        Schema::table('product_payment_partner_deliveries', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
