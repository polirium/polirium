<?php

declare(strict_types=1);

namespace Polirium\VietnamAdministrativeUnits\Data;

use Generator;
use RuntimeException;
use SplFileObject;

final class OfficialAdministrativeUnits
{
    public const EFFECTIVE_DATE = '2025-07-01';

    public const PROVINCE_COUNT = 34;

    public const WARD_COUNT = 3321;

    /**
     * @return array<string, array{code: string, name: string}>
     */
    public function provinces(): array
    {
        $provinces = [];

        foreach ($this->rows($this->path('provinces.csv')) as $row) {
            [$code, $name] = $row;
            $provinces[$code] = ['code' => $code, 'name' => $name];
        }

        if (count($provinces) !== self::PROVINCE_COUNT) {
            throw new RuntimeException('The official province dataset is incomplete.');
        }

        return $provinces;
    }

    /**
     * @return Generator<int, array{code: string, province_code: string, name: string, type: string}>
     */
    public function wards(): Generator
    {
        $count = 0;
        $codes = [];

        foreach ($this->rows($this->path('wards.csv')) as $row) {
            [$code, $provinceCode, $name, $type] = $row;

            if (isset($codes[$code])) {
                throw new RuntimeException("Duplicate commune-level code [{$code}] in the official dataset.");
            }

            $codes[$code] = true;
            $count++;

            yield [
                'code' => $code,
                'province_code' => $provinceCode,
                'name' => $name,
                'type' => $type,
            ];
        }

        if ($count !== self::WARD_COUNT) {
            throw new RuntimeException('The official commune-level dataset is incomplete.');
        }
    }

    /**
     * @return Generator<int, list<string>>
     */
    private function rows(string $path): Generator
    {
        $file = new SplFileObject($path, 'r');
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);
        $header = true;

        foreach ($file as $row) {
            if ($header) {
                $header = false;

                continue;
            }

            if (! is_array($row) || $row === [null]) {
                continue;
            }

            yield array_map(static fn (mixed $value): string => trim((string) $value), $row);
        }
    }

    private function path(string $filename): string
    {
        $path = __DIR__ . '/../../resources/data/' . $filename;

        if (! is_file($path)) {
            throw new RuntimeException("Administrative unit dataset not found: {$path}");
        }

        return $path;
    }
}
