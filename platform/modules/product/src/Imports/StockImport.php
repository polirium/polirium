<?php

namespace Polirium\Modules\Product\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Polirium\Modules\Product\Http\Model\Product;
use Polirium\Modules\Product\Http\Model\ProductBranch;

class StockImport implements ToCollection, WithHeadingRow
{
    /** @var array<int, array{product: Product, amount: int, actual_stock: int, quantity_difference: int, value: float, value_difference: float, note: string}> */
    public array $importedProducts = [];

    public array $errors = [];

    public function __construct(private readonly ?int $branchId = null)
    {
    }

    private const CODE_COLUMNS = [
        'ma_hang',
        'mã_hàng',
        'ma_hang_hoa',
        'mã_hàng_hoá',
        'mã_hàng_hóa',
        'code',
    ];

    private const ACTUAL_STOCK_COLUMNS = [
        'so_luong_thuc_te',
        'số_lượng_thực_tế',
        'thuc_te',
        'thực_tế',
        'so_luong',
        'số_lượng',
        'actual_stock',
        'quantity',
        'qty',
    ];

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $code = $this->extractCode($row);
            $actualStock = $this->extractActualStock($row);

            if (empty($code)) {
                continue;
            }

            $product = Product::where('code', $code)->first();

            if (! $product) {
                $this->errors[] = 'Dòng ' . ($index + 2) . ": Không tìm thấy sản phẩm mã '{$code}'";

                continue;
            }

            $branchStock = $this->getProductBranchStock((int) $product->id);
            $quantityDifference = $actualStock - $branchStock;
            $valueDifference = $quantityDifference * ($product->cost ?? 0);

            $this->importedProducts[$product->id] = [
                'product' => $product,
                'amount' => $branchStock,
                'actual_stock' => $actualStock,
                'quantity_difference' => $quantityDifference,
                'value' => $product->cost ?? 0,
                'value_difference' => $valueDifference,
                'note' => '',
            ];
        }
    }

    protected function extractCode(Collection $row): string
    {
        return trim((string) $this->firstFilledValue($row, self::CODE_COLUMNS, ''));
    }

    protected function extractActualStock(Collection $row): int
    {
        return (int) $this->parseNumber($this->firstFilledValue($row, self::ACTUAL_STOCK_COLUMNS, 0));
    }

    protected function firstFilledValue(Collection $row, array $columns, mixed $default = null): mixed
    {
        foreach ($columns as $column) {
            $value = $row->get($column);

            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return $default;
    }

    protected function parseNumber(mixed $value): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $value = trim((string) $value);
        if ($value === '') {
            return 0;
        }

        $value = preg_replace('/[^\d,.\-]/', '', $value) ?: '';

        if (str_contains($value, ',') && str_contains($value, '.')) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } elseif (preg_match('/^\d{1,3}(\.\d{3})+$/', $value)) {
            $value = str_replace('.', '', $value);
        } else {
            $value = str_replace(',', '.', $value);
        }

        return is_numeric($value) ? (float) $value : 0;
    }

    private function getProductBranchStock(int $productId): int
    {
        if (empty($this->branchId)) {
            return 0;
        }

        return (int) ProductBranch::query()
            ->where('product_id', $productId)
            ->where('branch_id', $this->branchId)
            ->value('qty');
    }
}
