<?php

namespace Tests\Unit;

use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use Polirium\Modules\Product\Imports\StockImport;

class StockImportTest extends TestCase
{
    public function test_extracts_stock_count_template_columns(): void
    {
        $import = $this->makeImport();

        $row = collect([
            'ma_hang' => 'PK_HLX-CU01.C',
            'so_luong' => 126,
        ]);

        $this->assertSame('PK_HLX-CU01.C', $import->code($row));
        $this->assertSame(126, $import->actualStock($row));
    }

    public function test_extracts_vietnamese_stock_count_aliases(): void
    {
        $import = $this->makeImport();

        $row = collect([
            'mã_hàng_hoá' => 'XG.901159',
            'thực_tế' => '1.039',
        ]);

        $this->assertSame('XG.901159', $import->code($row));
        $this->assertSame(1039, $import->actualStock($row));
    }

    public function test_extracts_common_export_quantity_aliases(): void
    {
        $import = $this->makeImport();

        $cases = [
            ['ma_hang' => 'XG.000544', 'ton_kho' => 12],
            ['ma_hang' => 'PK_HQS.K', 'sl' => 3],
            ['ma_hang' => 'XG.003550', 'sl_thuc_te' => 7],
            ['ma_hang' => 'XG.003551', 'so_luong_kiem_ke' => 9],
        ];

        foreach ($cases as $data) {
            $row = collect($data);
            $this->assertSame($data['ma_hang'], $import->code($row));
            $this->assertSame((int) array_values($data)[1], $import->actualStock($row));
        }
    }

    public function test_falls_back_to_second_column_on_two_column_sheet(): void
    {
        $import = $this->makeImport();

        $row = collect([
            'col_a' => 'XG.000544',
            'col_b' => 15,
        ]);

        $this->assertSame('XG.000544', $import->code($row));
        $this->assertSame(15, $import->actualStock($row));
    }

    public function test_reads_explicit_zero_quantity(): void
    {
        $import = $this->makeImport();

        $row = collect([
            'ma_hang' => 'XG.000544',
            'so_luong_thuc_te' => 0,
        ]);

        $this->assertSame(0, $import->actualStock($row));
    }

    private function makeImport(): object
    {
        $this->loadStockImport();

        return new class () extends StockImport {
            public function code(Collection $row): string
            {
                return $this->extractCode($row);
            }

            public function actualStock(Collection $row): int
            {
                return $this->extractActualStock($row)[0];
            }
        };
    }

    private function loadStockImport(): void
    {
        require_once dirname(__DIR__, 2) . '/platform/modules/product/src/Imports/StockImport.php';
    }
}
