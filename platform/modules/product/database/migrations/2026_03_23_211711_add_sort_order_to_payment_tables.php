<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('is_default');
        });

        Schema::table('product_payment_sale_channels', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('description');
        });

        Schema::table('product_payment_partner_deliveries', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('note');
        });
    }

    public function down(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });

        Schema::table('product_payment_sale_channels', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });

        Schema::table('product_payment_partner_deliveries', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
