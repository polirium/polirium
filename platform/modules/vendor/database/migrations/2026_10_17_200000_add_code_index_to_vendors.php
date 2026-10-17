<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Supplier look-ups by code (header search) had no index to use.
 */
return new class () extends Migration {
    private const INDEX = 'vendors_code_index';

    public function up(): void
    {
        if (Schema::hasTable('vendors') && ! Schema::hasIndex('vendors', self::INDEX)) {
            Schema::table('vendors', fn (Blueprint $table) => $table->index('code', self::INDEX));
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('vendors') && Schema::hasIndex('vendors', self::INDEX)) {
            Schema::table('vendors', fn (Blueprint $table) => $table->dropIndex(self::INDEX));
        }
    }
};
