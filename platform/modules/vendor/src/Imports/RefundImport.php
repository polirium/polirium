<?php

namespace Polirium\Modules\Vendor\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Polirium\Modules\Product\Http\Model\Product;

class RefundImport implements ToCollection, WithHeadingRow
{
    protected array $products = [];
    protected array $errors = [];

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +2 because of header row and 0-index

            try {
                $this->processRow($row, $rowNumber);
            } catch (\Throwable $th) {
                $this->errors[] = "Dòng {$rowNumber}: " . $th->getMessage();
            }
        }
    }

    protected function processRow(Collection $row, int $rowNumber): void
    {
        $code = trim($row['ma_hang'] ?? $row['mã_hàng'] ?? '');

        if (empty($code)) {
            $this->errors[] = "Dòng {$rowNumber}: Mã hàng không được để trống";
            return;
        }

        // Lookup product by code
        $product = Product::where('code', $code)->first();

        if (!$product) {
            $this->errors[] = "Dòng {$rowNumber}: Không tìm thấy sản phẩm với mã '{$code}'";
            return;
        }

        $amount = $this->parseNumber($row['so_luong'] ?? $row['số_lượng'] ?? 1);
        $price = $this->parseNumber($row['gia_tra_lai'] ?? $row['giá_trả_lại'] ?? $product->cost);
        $discount = $this->parseNumber($row['giam_gia_tra_lai'] ?? $row['giảm_giá_trả_lại'] ?? 0);

        // Calculate value
        $value = ($price * $amount) - $discount; // Assuming discount is total amount, or per item?
                                                 // "Giảm giá trả lại" usually means total reduction on the refund value.
                                                 // If it's per item, it would be ($price * $amount) - ($discount * $amount).
                                                 // Let's assume it's TOTAL discount for that line for now, or match Purchase logic?
                                                 // In Purchase, "Giảm giá" was treated as Unit Discount.
                                                 // Let's treat this as Unit Discount too for consistency, unless "Giảm giá trả lại" suggests otherwise.
                                                 // Actually, let's keep it safe: ($price - $discount) * $amount.

        $value = ($price - $discount) * $amount;

        $this->products[$product->id] = [
            'product_id' => $product->id,
            'amount' => $amount,
            'price' => $price,
            'value' => $value, // Total value after discount
            'discount' => $discount, // Store discount unit value if needed by component
            'note' => null,
            'product' => $product->toArray(),
        ];
    }

    protected function parseNumber($value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }
        return 0;
    }

    public function getProducts(): array
    {
        return $this->products;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
