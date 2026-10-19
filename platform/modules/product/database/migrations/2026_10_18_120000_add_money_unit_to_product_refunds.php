<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * money_unit records the unit a POS return's amounts are stored in.
 *
 * Returns saved before 1.1 kept total_payable_amount, value and the line
 * price / discount_value / value multiplied by 100, while
 * original_total_amount, discount_value and refund_fee were plain VND.
 * New returns store every amount in VND and set money_unit = 'vnd'.
 * Existing rows keep NULL and are read as the old layout; nothing is
 * rewritten here. `php artisan refunds:convert-money` converts them on
 * request.
 */
return new class () extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('product_refunds') && ! Schema::hasColumn('product_refunds', 'money_unit')) {
            Schema::table('product_refunds', function (Blueprint $table) {
                $table->string('money_unit', 8)->nullable()->after('value');
            });
        }
    }

    public function down(): void
    {
        // Without the column, VND returns would be read as the old x100 layout
        // and shown 100 times too small. Refuse instead of losing that marker.
        if (Schema::hasTable('product_refunds') && Schema::hasColumn('product_refunds', 'money_unit')
            && DB::table('product_refunds')->whereNotNull('money_unit')->exists()) {
            throw new RuntimeException('product_refunds has returns stored in VND (money_unit set); dropping money_unit would misread them. Not rolled back.');
        }

        if (Schema::hasTable('product_refunds') && Schema::hasColumn('product_refunds', 'money_unit')) {
            Schema::table('product_refunds', function (Blueprint $table) {
                $table->dropColumn('money_unit');
            });
        }
    }
};
