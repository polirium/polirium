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
        $code = trim((string) ($row['ma_hang'] ?? $row['mã_hàng'] ?? $row['ma'] ?? ''));

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

        $amount = $this->parseNumber($row['so_luong'] ?? $row['số_lượng'] ?? 0);
        $price = $this->parseNumber($row['gia_tra_lai'] ?? $row['giá_trả_lại'] ?? $row['gia_nhap'] ?? $row['giá_nhập'] ?? $product->cost);
        $discount = $this->parseNumber($row['giam_gia_tra_lai'] ?? $row['giảm_giá_trả_lại'] ?? 0);
        $discountPercent = $this->parseNumber($row['giam_gia_tra_lai_'] ?? $row['giảm_giá_trả_lại_'] ?? 0);

        if ($amount <= 0) {
            throw new \InvalidArgumentException('Số lượng phải lớn hơn 0');
        }
        if ($price < 0 || $discount < 0 || $discountPercent < 0 || $discountPercent > 100) {
            throw new \InvalidArgumentException('Giá hoặc giảm giá không hợp lệ');
        }

        // Calculate value
        $value = ($price * $amount) - $discount; // Assuming discount is total amount, or per item?
                                                 // "Giảm giá trả lại" usually means total reduction on the refund value.
                                                 // If it's per item, it would be ($price * $amount) - ($discount * $amount).
                                                 // Let's assume it's TOTAL discount for that line for now, or match Purchase logic?
                                                 // In Purchase, "Giảm giá" was treated as Unit Discount.
                                                 // Let's treat this as Unit Discount too for consistency, unless "Giảm giá trả lại" suggests otherwise.
                                                 // Actually, let's keep it safe: ($price - $discount) * $amount.

        $value = ($price - $discount) * $amount;
        if ($discountPercent > 0) {
            $value = ($price * $amount) * (1 - $discountPercent / 100);
        }

        $this->products[$product->id] = [
            'product_id' => $product->id,
            'amount' => $amount,
            'price' => $price,
            'value' => $value, // Total value after discount
            'discount_value' => $discountPercent > 0 ? $discountPercent : $discount,
            'discount_type' => $discountPercent > 0 ? 'percent' : 'number',
            'note' => null,
            'product' => $product->toArray(),
        ];
    }

    protected function parseNumber($value): float
    {
        if ($value === null || $value === '') {
            return 0;
        }
        // Keep native Excel numeric cells as-is. Numeric-looking strings may
        // still use Vietnamese thousands separators (for example 41.000).
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $value = trim((string) $value);
        $value = preg_replace('/[^0-9,.\-]/u', '', $value) ?? '';
        if ($value === '') {
            return 0;
        }

        // Vietnamese-formatted values: 1.234.567 or 1,234,567.
        if (substr_count($value, ',') > 1 || substr_count($value, '.') > 1) {
            $value = str_replace([',', '.'], '', $value);
        } elseif (str_contains($value, ',') && str_contains($value, '.')) {
            $value = str_replace(',', '', $value);
        } elseif (str_contains($value, ',')) {
            $parts = explode(',', $value);
            $value = strlen(end($parts)) === 3 ? implode('', $parts) : implode('.', $parts);
        } elseif (str_contains($value, '.')) {
            $parts = explode('.', $value);
            $value = strlen(end($parts)) === 3 ? implode('', $parts) : $value;
        }

        return is_numeric($value) ? (float) $value : 0;
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
