<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('provinces', 'published')) {
            Schema::table('provinces', function (Blueprint $table): void {
                $table->boolean('published')->default(true)->after('gso_id')->index();
            });
        }

        if (! Schema::hasColumn('districts', 'published')) {
            Schema::table('districts', function (Blueprint $table): void {
                $table->boolean('published')->default(true)->after('province_id')->index();
            });
        }

        Schema::table('wards', function (Blueprint $table): void {
            if (! Schema::hasColumn('wards', 'province_id')) {
                $table->unsignedBigInteger('province_id')->nullable()->after('district_id')->index();
                $table->foreign('province_id')->references('id')->on('provinces')->nullOnDelete();
            }

            if (! Schema::hasColumn('wards', 'type')) {
                $table->string('type', 20)->nullable()->after('name');
            }

            if (! Schema::hasColumn('wards', 'published')) {
                $table->boolean('published')->default(true)->after('province_id')->index();
            }

            $table->unsignedBigInteger('district_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::table('wards')->whereNull('district_id')->exists()) {
            throw new \RuntimeException(
                'Cannot roll back two-tier administrative units while wards without a legacy district exist. '
                . 'Restore a compatible backup or assign every ward to a district first.',
            );
        }

        Schema::table('wards', function (Blueprint $table): void {
            if (Schema::hasColumn('wards', 'province_id')) {
                $table->dropForeign(['province_id']);
                $table->dropColumn('province_id');
            }

            foreach (['type', 'published'] as $column) {
                if (Schema::hasColumn('wards', $column)) {
                    $table->dropColumn($column);
                }
            }

            $table->unsignedBigInteger('district_id')->nullable(false)->change();
        });

        foreach (['districts', 'provinces'] as $tableName) {
            if (Schema::hasColumn($tableName, 'published')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->dropColumn('published');
                });
            }
        }
    }
};
