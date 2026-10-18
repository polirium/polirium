<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * - checkout_key: one key per sale or return attempt, kept across retries.
 *   Saving the same key twice returns the first document instead of
 *   creating (and posting stock for) a second one. Existing rows keep NULL,
 *   which the unique index allows any number of times.
 * - product_pos_carts: carts open at the point of sale, so a reload, a
 *   closed browser or an expired session does not lose them.
 */
return new class () extends Migration {
    private const KEYED_TABLES = ['product_payments', 'product_refunds'];

    public function up(): void
    {
        foreach (self::KEYED_TABLES as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'checkout_key')) {
                Schema::table($table, function (Blueprint $blueprint) use ($table) {
                    $blueprint->string('checkout_key', 36)->nullable()->after('id');
                    $blueprint->unique('checkout_key', "{$table}_checkout_key_unique");
                });
            }
        }

        if (! Schema::hasTable('product_pos_carts')) {
            Schema::create('product_pos_carts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->string('tab_key', 40);
                $table->string('type', 16)->default('invoice');
                $table->unsignedInteger('position')->default(0);
                $table->longText('state')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'tab_key']);
                $table->index(['user_id', 'branch_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_pos_carts');

        foreach (self::KEYED_TABLES as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'checkout_key')) {
                Schema::table($table, function (Blueprint $blueprint) use ($table) {
                    $blueprint->dropUnique("{$table}_checkout_key_unique");
                    $blueprint->dropColumn('checkout_key');
                });
            }
        }
    }
};
