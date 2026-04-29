<?php

namespace Polirium\Modules\Vendor\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Polirium\Modules\Product\Http\Model\Product;

class PurchaseImport implements ToCollection, WithHeadingRow
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

        $price = $this->parseNumber($row['don_gia'] ?? $row['đơn_giá'] ?? $product->cost);
        $amount = $this->parseNumber($row['so_luong'] ?? $row['số_lượng'] ?? 1);
        $discountValue = $this->parseNumber($row['giam_gia'] ?? $row['giảm_giá'] ?? 0);

        // New template only has "Giảm giá" which we assume is value.
        // If "Giảm giá (%)" existed it would likely be slugified to 'giam_gia_' or similar.
        // We will support checking for 'giam_gia_' just in case but default to using 'giam_gia' as value.
        $discountPercent = $this->parseNumber($row['giam_gia_'] ?? $row['giảm_giá_%'] ?? 0);

        // Determine discount type
        $discountType = 'number';
        $discount = $discountValue;

        if ($discountPercent > 0) {
            $discountType = 'percent';
            $discount = $discountPercent;
        }

        // Calculate value
        $value = $price * $amount;
        if ($discountType === 'percent') {
            $value = $value - ($value * $discount / 100);
        } else {
            $value = $value - ($discount * $amount); // Discount is per item or total? Usually per item in these contexts but "Giảm giá" column could be total discount line.
                                                     // However, in Polirium context usually discount is applied to the line total or unit price.
                                                     // Let's assume it's discount PER UNIT if standard, or TOTAL discount?
                                                     // Based on previous code: $value = $value - ($discount * $amount); -> Implies discount is PER UNIT.
                                                     // Wait, ($discount * $amount) means $discount is PER UNIT.
                                                     // If the column "Giảm giá" means total discount for the line, logic would be different.
                                                     // Given "Đơn giá" (Unit Price) and "Số lượng", "Giảm giá" is likely Unit Discount or Total Discount.
                                                     // Standard is usually Line Discount or Unit Discount.
                                                     // I will stick to existing logic: Discount is PER UNIT.
        }

        $this->products[$product->id] = [
            'product_id' => $product->id,
            'amount' => $amount,
            'price' => $price,
            'discount_value' => $discount,
            'discount_type' => $discountType,
            'value' => $value,
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
