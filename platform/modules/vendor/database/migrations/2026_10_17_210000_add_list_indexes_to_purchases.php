<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The purchase list filters by branch and date and loads each purchase's
 * lines; none of those columns had an index.
 */
return new class () extends Migration {
    private const INDEXES = [
        ['vendor_purchases', ['branch_id', 'created_at'], 'vendor_purchases_branch_created_index'],
        ['vendor_purchase_products', ['vendor_purchase_id'], 'vendor_purchase_products_purchase_index'],
    ];

    public function up(): void
    {
        foreach (self::INDEXES as [$table, $columns, $name]) {
            if (Schema::hasTable($table) && ! Schema::hasIndex($table, $columns)) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->index($columns, $name));
            }
        }
    }

    public function down(): void
    {
        foreach (self::INDEXES as [$table, , $name]) {
            if (Schema::hasTable($table) && Schema::hasIndex($table, $name)) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropIndex($name));
            }
        }
    }
};
