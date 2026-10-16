<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        foreach (['provinces', 'districts', 'wards'] as $tableName) {
            $duplicateCode = DB::table($tableName)
                ->select('gso_id')
                ->groupBy('gso_id')
                ->havingRaw('COUNT(*) > 1')
                ->value('gso_id');

            if ($duplicateCode !== null) {
                throw new \RuntimeException(
                    "Cannot enforce unique administrative codes: [{$tableName}.gso_id] contains duplicate [{$duplicateCode}].",
                );
            }
        }

        foreach (['provinces', 'districts', 'wards'] as $tableName) {
            $hasUniqueCodeIndex = collect(Schema::getIndexes($tableName))->contains(
                static fn (array $index): bool => ($index['unique'] ?? false)
                    && ($index['columns'] ?? []) === ['gso_id'],
            );

            if (! $hasUniqueCodeIndex) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                    $table->unique('gso_id', $tableName . '_gso_id_unique');
                });
            }
        }
    }

    public function down(): void
    {
        if (DB::table('wards')->whereNull('district_id')->exists()) {
            throw new \RuntimeException(
                'Cannot remove administrative-unit unique indexes while two-tier wards exist.',
            );
        }

        Schema::table('wards', function (Blueprint $table): void {
            $table->dropUnique('wards_gso_id_unique');
        });

        Schema::table('districts', function (Blueprint $table): void {
            $table->dropUnique('districts_gso_id_unique');
        });

        Schema::table('provinces', function (Blueprint $table): void {
            $table->dropUnique('provinces_gso_id_unique');
        });
    }
};
