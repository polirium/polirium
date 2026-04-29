<?php

namespace Polirium\Modules\Product\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Polirium\Modules\Product\Http\Model\Product;

class StockImport implements ToCollection, WithHeadingRow
{
    /** @var array<int, array{product: Product, amount: int, actual_stock: int, quantity_difference: int, value: float, value_difference: float, note: string}> */
    public array $importedProducts = [];

    public array $errors = [];

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $code = trim($row['ma_hang'] ?? $row['code'] ?? '');
            $actualStock = (int) ($row['so_luong_thuc_te'] ?? $row['actual_stock'] ?? $row['quantity'] ?? 0);

            if (empty($code)) {
                continue;
            }

            $product = Product::where('code', $code)->first();

            if (! $product) {
                $this->errors[] = 'Dòng ' . ($index + 2) . ": Không tìm thấy sản phẩm mã '{$code}'";

                continue;
            }

            $branchStock = $product->amount ?? 0;
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
}
