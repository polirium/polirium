<?php

declare(strict_types=1);

namespace Polirium\VietnamAdministrativeUnits\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Polirium\VietnamAdministrativeUnits\Data\OfficialAdministrativeUnits;
use RuntimeException;

final class SyncAdministrativeUnitsCommand extends Command
{
    protected $signature = 'vietnam-units:sync
        {--apply : Write the proposed changes}
        {--archive-missing : Hide legacy units absent from the official list without deleting them}';

    protected $description = 'Preview or synchronize Vietnam administrative units from the bundled official dataset';

    public function handle(OfficialAdministrativeUnits $dataset): int
    {
        $this->ensureSchemaIsReady();

        $provinces = $dataset->provinces();
        $wards = iterator_to_array($dataset->wards(), false);
        $provinceCodes = array_keys($provinces);
        $wardCodes = array_column($wards, 'code');
        $existingProvinceCount = DB::table('provinces')->whereIn('gso_id', $provinceCodes)->count();
        $existingWardCount = DB::table('wards')->whereIn('gso_id', $wardCodes)->count();

        $summary = [
            ['Province-level units in official data', count($provinces)],
            ['Commune-level units in official data', count($wards)],
            ['New province-level units', count($provinces) - $existingProvinceCount],
            ['New commune-level units', count($wards) - $existingWardCount],
            ['Legacy provinces to archive', DB::table('provinces')->where('published', true)->whereNotIn('gso_id', $provinceCodes)->count()],
            ['Legacy districts to archive', DB::table('districts')->where('published', true)->count()],
            ['Legacy commune-level units to archive', DB::table('wards')->where('published', true)->whereNotIn('gso_id', $wardCodes)->count()],
        ];

        $this->table(['Change', 'Count'], $summary);
        $this->line('Source effective date: ' . OfficialAdministrativeUnits::EFFECTIVE_DATE);

        if (! $this->option('apply')) {
            $this->warn('Dry run only. Re-run with --apply after reviewing this summary.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($provinces, $wards, $provinceCodes, $wardCodes): void {
            $now = now();

            DB::table('provinces')->upsert(
                array_map(static fn (array $province): array => [
                    'gso_id' => $province['code'],
                    'name' => $province['name'],
                    'published' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], array_values($provinces)),
                ['gso_id'],
                ['name', 'published', 'updated_at'],
            );

            $provinceIds = DB::table('provinces')
                ->whereIn('gso_id', $provinceCodes)
                ->pluck('id', 'gso_id');

            foreach (array_chunk($wards, 500) as $chunk) {
                DB::table('wards')->upsert(
                    array_map(static function (array $ward) use ($provinceIds, $now): array {
                        $provinceId = $provinceIds[$ward['province_code']] ?? null;

                        if ($provinceId === null) {
                            throw new RuntimeException("Unknown province code [{$ward['province_code']}].");
                        }

                        return [
                            'gso_id' => $ward['code'],
                            'province_id' => $provinceId,
                            'name' => $ward['name'],
                            'type' => $ward['type'],
                            'published' => true,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }, $chunk),
                    ['gso_id'],
                    ['province_id', 'name', 'type', 'published', 'updated_at'],
                );
            }

            if ($this->option('archive-missing')) {
                DB::table('provinces')->where('published', true)->whereNotIn('gso_id', $provinceCodes)->update([
                    'published' => false,
                    'updated_at' => $now,
                ]);
                DB::table('districts')->where('published', true)->update([
                    'published' => false,
                    'updated_at' => $now,
                ]);
                DB::table('wards')->where('published', true)->whereNotIn('gso_id', $wardCodes)->update([
                    'published' => false,
                    'updated_at' => $now,
                ]);
            }
        });

        $this->clearLocationCaches($provinceCodes);
        $this->info('Vietnam administrative units synchronized successfully.');

        return self::SUCCESS;
    }

    private function ensureSchemaIsReady(): void
    {
        foreach (['provinces', 'districts', 'wards'] as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException("Missing [{$table}] table. Run migrations before synchronizing locations.");
            }
        }

        foreach (['province_id', 'type', 'published'] as $column) {
            if (! Schema::hasColumn('wards', $column)) {
                throw new RuntimeException("Missing [wards.{$column}] column. Run migrations before synchronizing locations.");
            }
        }

        foreach (['provinces', 'districts', 'wards'] as $table) {
            $hasUniqueCodeIndex = collect(Schema::getIndexes($table))->contains(
                static fn (array $index): bool => ($index['unique'] ?? false)
                    && ($index['columns'] ?? []) === ['gso_id'],
            );

            if (! $hasUniqueCodeIndex) {
                throw new RuntimeException(
                    "Missing a unique index for [{$table}.gso_id]. Run migrations before synchronizing locations.",
                );
            }
        }
    }

    /**
     * @param list<string> $provinceCodes
     */
    private function clearLocationCaches(array $provinceCodes): void
    {
        Cache::forget('provinces');

        $provinceIds = DB::table('provinces')->whereIn('gso_id', $provinceCodes)->pluck('id');
        foreach ($provinceIds as $provinceId) {
            Cache::forget('districts_' . $provinceId);
            Cache::forget('wards_by_province_' . $provinceId);
        }

        foreach (DB::table('districts')->pluck('id') as $districtId) {
            Cache::forget('wards_' . $districtId);
        }
    }
}
