<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Look-ups by code (header search, barcode scans, invoice codes) could not
 * use an index: products only had a composite (name, code) index and sales
 * invoices none.
 */
return new class () extends Migration {
    private const INDEXES = [
        'products' => 'products_code_index',
        'product_payments' => 'product_payments_code_index',
    ];

    public function up(): void
    {
        foreach (self::INDEXES as $table => $index) {
            if (Schema::hasTable($table) && ! Schema::hasIndex($table, ['code'])) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->index('code', $index));
            }
        }
    }

    public function down(): void
    {
        foreach (self::INDEXES as $table => $index) {
            if (Schema::hasTable($table) && Schema::hasIndex($table, $index)) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropIndex($index));
            }
        }
    }
};
