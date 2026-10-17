<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The product list filters and joins by group, brand and shelf; none of
 * those columns had an index.
 */
return new class () extends Migration {
    private const COLUMNS = ['category_id', 'trademark_id', 'shelve_id'];

    public function up(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        foreach (self::COLUMNS as $column) {
            if (Schema::hasColumn('products', $column) && ! Schema::hasIndex('products', [$column])) {
                Schema::table('products', fn (Blueprint $table) => $table->index($column, "products_{$column}_index"));
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        foreach (self::COLUMNS as $column) {
            if (Schema::hasIndex('products', "products_{$column}_index")) {
                Schema::table('products', fn (Blueprint $table) => $table->dropIndex("products_{$column}_index"));
            }
        }
    }
};
