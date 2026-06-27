<?php

namespace Tests\Unit;

use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use Polirium\Modules\Product\Imports\StockImport;

class StockImportTest extends TestCase
{
    public function test_extracts_stock_count_template_columns(): void
    {
        $this->loadStockImport();

        $import = new class () extends StockImport {
            public function code(Collection $row): string
            {
                return $this->extractCode($row);
            }

            public function actualStock(Collection $row): int
            {
                return $this->extractActualStock($row);
            }
        };

        $row = collect([
            'ma_hang' => 'PK_HLX-CU01.C',
            'so_luong' => 126,
        ]);

        $this->assertSame('PK_HLX-CU01.C', $import->code($row));
        $this->assertSame(126, $import->actualStock($row));
    }

    public function test_extracts_vietnamese_stock_count_aliases(): void
    {
        $this->loadStockImport();

        $import = new class () extends StockImport {
            public function code(Collection $row): string
            {
                return $this->extractCode($row);
            }

            public function actualStock(Collection $row): int
            {
                return $this->extractActualStock($row);
            }
        };

        $row = collect([
            'mã_hàng_hoá' => 'XG.901159',
            'thực_tế' => '1.039',
        ]);

        $this->assertSame('XG.901159', $import->code($row));
        $this->assertSame(1039, $import->actualStock($row));
    }

    private function loadStockImport(): void
    {
        require_once dirname(__DIR__, 2) . '/platform/modules/product/src/Imports/StockImport.php';
    }
}
