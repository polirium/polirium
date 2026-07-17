<?php

namespace Polirium\Modules\Product\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Polirium\Modules\Product\Http\Model\Product;
use Polirium\Modules\Product\Http\Model\ProductBranch;

class StockImport implements ToCollection, WithHeadingRow
{
    /** @var array<int, array{product: array|Product, amount: int, actual_stock: int, quantity_difference: int, value: float, value_difference: float, note: string}> */
    public array $importedProducts = [];

    public array $errors = [];

    public array $warnings = [];

    private bool $missingQuantityColumn = false;

    public function __construct(private readonly ?int $branchId = null)
    {
    }

    private const CODE_COLUMNS = [
        'ma_hang',
        'mã_hàng',
        'ma_hang_hoa',
        'mã_hàng_hoá',
        'mã_hàng_hóa',
        'ma_sp',
        'mã_sp',
        'masp',
        'sku',
        'barcode',
        'code',
    ];

    private const ACTUAL_STOCK_COLUMNS = [
        'so_luong_thuc_te',
        'số_lượng_thực_tế',
        'sl_thuc_te',
        'sl',
        'thuc_te',
        'thực_tế',
        'so_luong',
        'số_lượng',
        'so_luong_kiem_ke',
        'số_lượng_kiểm_kê',
        'so_luong_kiem',
        'số_lượng_kiểm',
        'ton_kho',
        'tồn_kho',
        'ton_kiem',
        'tồn_kiểm',
        'kiem_dem',
        'kiểm_đếm',
        'actual_stock',
        'quantity',
        'qty',
        'count',
    ];

    private const SKIP_FALLBACK_COLUMNS = [
        'ten_hang',
        'tên_hàng',
        'name',
        'dvt',
        'đvt',
        'unit',
        'gia_von',
        'giá_vốn',
        'gia_ban',
        'giá_bán',
        'cost',
        'price',
        'ghi_chu',
        'ghi_chú',
        'note',
    ];

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $code = $this->extractCode($row);
            [$actualStock, $quantityFromKnownColumn] = $this->extractActualStock($row);

            if (empty($code)) {
                continue;
            }

            $product = Product::where('code', $code)->first();

            if (! $product) {
                $this->errors[] = 'Dòng ' . ($index + 2) . ": Không tìm thấy sản phẩm mã '{$code}'";

                continue;
            }

            if (! $quantityFromKnownColumn && $actualStock === 0) {
                $this->missingQuantityColumn = true;
            }

            $branchStock = $this->getProductBranchStock((int) $product->id);
            $quantityDifference = $actualStock - $branchStock;
            $valueDifference = $quantityDifference * ($product->cost ?? 0);

            $this->importedProducts[$product->id] = [
                'product' => $product->toArray(),
                'amount' => $branchStock,
                'actual_stock' => $actualStock,
                'quantity_difference' => $quantityDifference,
                'value' => $product->cost ?? 0,
                'value_difference' => $valueDifference,
                'note' => '',
            ];
        }

        if ($this->missingQuantityColumn && count($this->importedProducts) > 0) {
            $this->warnings[] = 'Không nhận diện được cột số lượng. Hãy dùng header "Số lượng thực tế" (hoặc SL / Tồn kho).';
        }
    }

    protected function extractCode(Collection $row): string
    {
        $code = trim((string) $this->firstFilledValue($row, self::CODE_COLUMNS, ''));

        if ($code !== '') {
            return $code;
        }

        // Fallback: first non-empty cell when headers are unknown.
        foreach ($row as $key => $value) {
            if (is_string($key) && in_array($key, self::ACTUAL_STOCK_COLUMNS, true)) {
                continue;
            }

            $candidate = trim((string) ($value ?? ''));
            if ($candidate !== '' && ! $this->looksLikeQuantity($candidate)) {
                return $candidate;
            }
        }

        return '';
    }

    /**
     * @return array{0: int, 1: bool} [quantity, foundInKnownColumn]
     */
    protected function extractActualStock(Collection $row): array
    {
        $value = $this->firstFilledValue($row, self::ACTUAL_STOCK_COLUMNS, null);

        if ($value !== null && $value !== '') {
            return [(int) $this->parseNumber($value), true];
        }

        // Explicit zero in a known quantity column.
        foreach (self::ACTUAL_STOCK_COLUMNS as $column) {
            if ($row->has($column) && ($row->get($column) === 0 || $row->get($column) === '0')) {
                return [0, true];
            }
        }

        $fallback = $this->fallbackQuantityValue($row);

        if ($fallback !== null && $fallback !== '') {
            return [(int) $this->parseNumber($fallback), false];
        }

        if ($fallback === 0 || $fallback === '0') {
            return [0, false];
        }

        return [0, false];
    }

    protected function fallbackQuantityValue(Collection $row): mixed
    {
        $skipKeys = array_merge(self::CODE_COLUMNS, self::SKIP_FALLBACK_COLUMNS);

        foreach ($row as $key => $value) {
            if (is_string($key) && in_array($key, $skipKeys, true)) {
                continue;
            }

            if ($value === null || $value === '') {
                continue;
            }

            if ($this->looksLikeQuantity($value)) {
                return $value;
            }
        }

        // Two-column sheet: code + qty (even with odd headers).
        if ($row->count() === 2) {
            foreach ($row as $key => $value) {
                if (is_string($key) && in_array($key, self::CODE_COLUMNS, true)) {
                    continue;
                }

                return $value;
            }

            return $row->values()->get(1);
        }

        return null;
    }

    protected function looksLikeQuantity(mixed $value): bool
    {
        if (is_int($value) || is_float($value)) {
            return true;
        }

        $asString = trim((string) $value);

        if ($asString === '' || $asString === '-' ) {
            return false;
        }

        if ($asString === '0') {
            return true;
        }

        return (bool) preg_match('/^-?\d{1,3}([.,]\d{3})+([.,]\d+)?$/', $asString)
            || (bool) preg_match('/^-?\d+([.,]\d+)?$/', $asString);
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
        $branchId = $this->resolveBranchId();

        if ($branchId === null) {
            return 0;
        }

        return (int) ProductBranch::query()
            ->where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->value('qty');
    }

    private function resolveBranchId(): ?int
    {
        if (! empty($this->branchId)) {
            return (int) $this->branchId;
        }

        if (function_exists('user_branch')) {
            $branchId = user_branch();

            return $branchId ? (int) $branchId : null;
        }

        return null;
    }
}
