<?php

declare(strict_types=1);

namespace App\Installer\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ExistingDatabaseInspector
{
    /**
     * @return array{status: 'empty'|'polirium'|'unknown', table_count: int, has_modules_table: bool}
     */
    public function inspect(): array
    {
        $connection = DB::connection();
        $connection->getPdo();
        $database = (string) $connection->getDatabaseName();

        $tables = array_map(
            'strtolower',
            Schema::getTableListing($database, false),
        );
        $migrations = [];

        if (in_array('migrations', $tables, true)) {
            try {
                $migrations = DB::table('migrations')->pluck('migration')->filter('is_string')->values()->all();
            } catch (Throwable) {
                // A foreign database may have an unrelated table with this name.
                // Treat it as unrecognized instead of failing the installer page.
            }
        }

        return $this->classify($tables, $migrations);
    }

    /**
     * @param  list<string>  $tables
     * @param  list<string>  $migrations
     * @return array{status: 'empty'|'polirium'|'unknown', table_count: int, has_modules_table: bool}
     */
    public function classify(array $tables, array $migrations = []): array
    {
        $tables = array_values(array_unique(array_map('strtolower', $tables)));

        if ($tables === []) {
            return ['status' => 'empty', 'table_count' => 0, 'has_modules_table' => false];
        }

        $signatureTables = [
            'brands',
            'customers',
            'modules',
            'product_payments',
            'products',
            'settings',
            'vendors',
        ];
        $hasSignatureTable = array_intersect($signatureTables, $tables) !== [];
        $hasSignatureMigration = collect($migrations)->contains(
            static fn (string $migration): bool => preg_match(
                '/(?:create|uuid)_(?:brands|customers|modules|products|settings|vendors)(?:_table)?/',
                strtolower($migration),
            ) === 1,
        );
        $isPolirium = in_array('migrations', $tables, true)
            && in_array('users', $tables, true)
            && ($hasSignatureTable || $hasSignatureMigration);

        return [
            'status' => $isPolirium ? 'polirium' : 'unknown',
            'table_count' => count($tables),
            'has_modules_table' => in_array('modules', $tables, true),
        ];
    }
}
