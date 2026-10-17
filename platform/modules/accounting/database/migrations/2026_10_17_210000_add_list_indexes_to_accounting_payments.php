<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The cash book list and dashboard filter receipts and payments by branch and
 * date, and look them up by the document they belong to; none of those
 * columns had an index.
 */
return new class () extends Migration {
    private const TABLE = 'accouting_payments';

    private const INDEXES = [
        [['branch_id', 'date'], 'accouting_payments_branch_date_index'],
        [['finance_type', 'finance_id'], 'accouting_payments_finance_index'],
    ];

    public function up(): void
    {
        if (! Schema::hasTable(self::TABLE)) {
            return;
        }

        foreach (self::INDEXES as [$columns, $name]) {
            if (! Schema::hasIndex(self::TABLE, $columns)) {
                Schema::table(self::TABLE, fn (Blueprint $table) => $table->index($columns, $name));
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::TABLE)) {
            return;
        }

        foreach (self::INDEXES as [, $name]) {
            if (Schema::hasIndex(self::TABLE, $name)) {
                Schema::table(self::TABLE, fn (Blueprint $table) => $table->dropIndex($name));
            }
        }
    }
};
