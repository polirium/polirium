<?php

namespace Polirium\Modules\Product\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Polirium\Core\Base\Http\Models\Branch\Branch;
use Polirium\Modules\Product\Http\Model\Category;
use Polirium\Modules\Product\Http\Model\Product;
use Polirium\Modules\Product\Http\Model\Shelve;
use Polirium\Modules\Product\Http\Model\Trademark;

class ProductImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    protected int $rowCount = 0;
    protected int $createdCount = 0;
    protected int $updatedCount = 0;
    protected array $errors = [];

    public function __construct(
        protected string $duplicateCodeHandling = 'error',
        protected string $duplicateBarcodeHandling = 'error',
        protected string $costPriceScope = 'global',
        protected ?int $costPriceBranchId = null,
        protected string $businessStatusScope = 'global',
        protected ?int $businessStatusBranchId = null,
        protected bool $updateStock = false,
        protected bool $updateDescription = false,
        protected bool $updateCostPrice = false,
    ) {
    }

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
        $name = trim($row['ten_hang'] ?? $row['tên_hàng'] ?? '');

        if (empty($code) || empty($name)) {
            $this->errors[] = "Dòng {$rowNumber}: Mã hàng hoặc tên hàng không được để trống";

            return;
        }

        // Check for duplicate product
        $existingProduct = Product::where('code', $code)->first();

        if ($existingProduct) {
            if ($this->duplicateCodeHandling === 'error') {
                $this->errors[] = "Dòng {$rowNumber}: Mã hàng '{$code}' đã tồn tại";

                return;
            }

            // Update existing product
            $this->updateProduct($existingProduct, $row);
            $this->updatedCount++;
        } else {
            // Create new product
            $this->createProduct($row);
            $this->createdCount++;
        }

        $this->rowCount++;
    }

    protected function createProduct(Collection $row): void
    {
        $categoryId = $this->parseCategory($row['nhom_hang'] ?? $row['nhóm_hàng'] ?? $row['nhom_hang3_cap'] ?? $row['nhóm_hàng3_cấp'] ?? null);
        $trademarkId = $this->parseOrCreateTrademark($row['thuong_hieu'] ?? $row['thương_hiệu'] ?? null);
        $shelveId = $this->parseOrCreateShelve($row['vi_tri'] ?? $row['vị_trí'] ?? null);
        $type = $this->parseType($row['loai_hang'] ?? $row['loại_hàng'] ?? 'Hàng hóa');
        $status = $this->parseStatus($row['dang_kinh_doanh'] ?? $row['đang_kinh_doanh'] ?? 'Có');

        $price = $this->parseNumber($row['gia_ban'] ?? $row['giá_bán'] ?? 0);
        $cost = $this->parseNumber($row['gia_von'] ?? $row['giá_vốn'] ?? 0);

        // Calculate min/max stock
        $minStock = $this->parseNumber($row['ton_nho_nhat'] ?? $row['tồn_nhỏ_nhất'] ?? 0);
        $maxStock = $this->parseNumber($row['ton_lon_nhat'] ?? $row['tồn_lớn_nhất'] ?? 999999999);

        $qty = $this->parseNumber($row['ton_kho'] ?? $row['tồn_kho'] ?? 0);

        $product = Product::create([
            'uuid' => Str::uuid(),
            'code' => trim($row['ma_hang'] ?? $row['mã_hàng'] ?? ''),
            'name' => trim($row['ten_hang'] ?? $row['tên_hàng'] ?? ''),
            'category_id' => $categoryId,
            'trademark_id' => $trademarkId,
            'shelve_id' => $shelveId,
            'type' => $type,
            'cost' => $cost,
            'price' => $price,
            'qty' => $this->updateStock ? $qty : 0,
            'unit' => trim($row['dvt'] ?? $row['đvt'] ?? ''),
            'min_quantity' => $minStock,
            'max_quantity' => $maxStock,
            'weight' => $this->parseNumber($row['trong_luong'] ?? $row['trọng_lượng'] ?? 0),
            'allows_sale' => $this->parseBoolean($row['duoc_ban_truc_tiep'] ?? $row['được_bán_trực_tiếp'] ?? 1),
            'description' => $row['mo_ta'] ?? $row['mô_tả'] ?? null,
            'note' => $row['ghi_chu'] ?? $row['ghi_chú'] ?? $row['mau_ghi_chu'] ?? $row['mẫu_ghi_chú'] ?? null,
            'user_id' => auth()->id(),
            // 'status' => $status, // Assuming there is a status column or trait (BaseModel often has 'status')
        ]);

        // Handle Images
        $this->processImages($product, $row['hinh_anh_url1url2'] ?? $row['hình_ảnh_url1url2'] ?? null);

        // Sync branches - always sync to make product visible in the list
        // If updateStock is false, sync with qty = 0
        $this->syncBranches($product, $this->updateStock ? $qty : 0);
    }

    protected function updateProduct(Product $product, Collection $row): void
    {
        $updateData = [
            'name' => trim($row['ten_hang'] ?? $row['tên_hàng'] ?? $product->name),
            'category_id' => $this->parseCategory($row['nhom_hang'] ?? $row['nhóm_hàng'] ?? $row['nhom_hang3_cap'] ?? $row['nhóm_hàng3_cấp'] ?? null) ?? $product->category_id,
            'trademark_id' => $this->parseOrCreateTrademark($row['thuong_hieu'] ?? $row['thương_hiệu'] ?? null) ?? $product->trademark_id,
            'shelve_id' => $this->parseOrCreateShelve($row['vi_tri'] ?? $row['vị_trí'] ?? null) ?? $product->shelve_id,
            'type' => $this->parseType($row['loai_hang'] ?? $row['loại_hàng'] ?? null) ?? $product->type,
            'price' => $this->parseNumber($row['gia_ban'] ?? $row['giá_bán'] ?? $product->price),
            'unit' => trim($row['dvt'] ?? $row['đvt'] ?? $product->unit ?? ''),
            'min_quantity' => $this->parseNumber($row['ton_nho_nhat'] ?? $row['tồn_nhỏ_nhất'] ?? $product->min_quantity),
            'max_quantity' => $this->parseNumber($row['ton_lon_nhat'] ?? $row['tồn_lớn_nhất'] ?? $product->max_quantity),
            'weight' => $this->parseNumber($row['trong_luong'] ?? $row['trọng_lượng'] ?? $product->weight),
            'allows_sale' => $this->parseBoolean($row['duoc_ban_truc_tiep'] ?? $row['được_bán_trực_tiếp'] ?? $product->allows_sale),
            'note' => $row['ghi_chu'] ?? $row['ghi_chú'] ?? $row['mau_ghi_chu'] ?? $row['mẫu_ghi_chú'] ?? $product->note,
        ];

        // Conditionally update cost price
        if ($this->updateCostPrice) {
            $updateData['cost'] = $this->parseNumber($row['gia_von'] ?? $row['giá_vốn'] ?? $product->cost);
        }

        // Conditionally update description
        if ($this->updateDescription) {
            $updateData['description'] = $row['mo_ta'] ?? $row['mô_tả'] ?? $product->description;
        }

        $product->update($updateData);

        // Conditionally update stock
        if ($this->updateStock) {
            $qty = $this->parseNumber($row['ton_kho'] ?? $row['tồn_kho'] ?? 0);
            $this->syncBranches($product, $qty);
        }

        // Handle Images (Update - Append or Replace? Usually append or ignore if existing. Let's append if new URLs provided)
        if (! empty($row['hinh_anh_url1url2'] ?? $row['hình_ảnh_url1url2'] ?? null)) {
            $this->processImages($product, $row['hinh_anh_url1url2'] ?? $row['hình_ảnh_url1url2']);
        }
    }

    protected function syncBranches(Product $product, int $qty): void
    {
        // Get user's branch or first available branch
        $branchId = user_branch();

        // Fallback to first branch if user has no branch
        if (! $branchId) {
            $firstBranch = Branch::first();
            if (! $firstBranch) {
                // Create default branch if none exists
                $firstBranch = Branch::create([
                    'uuid' => Str::uuid(),
                    'name' => 'Chi nhánh mặc định',
                    'user_id' => auth()->id(),
                ]);
            }
            $branchId = $firstBranch->id;
        }

        // Sync product to branch
        $product->branches()->syncWithoutDetaching([$branchId]);
        $product->branches()->updateExistingPivot($branchId, ['qty' => $qty]);
    }

    protected function processImages(Product $product, ?string $imageUrls): void
    {
        if (empty($imageUrls)) {
            return;
        }

        // Check if media helper exists
        if (! function_exists('media_upload_from_url')) {
            return;
        }

        $urls = explode(',', $imageUrls);
        foreach ($urls as $url) {
            $url = trim($url);
            if (empty($url) || ! filter_var($url, FILTER_VALIDATE_URL)) {
                continue;
            }

            try {
                media_upload_from_url($url, [
                    'model' => $product,
                    'collection' => 'products', // Or 'default'
                    'disk' => 'public',
                ]);
            } catch (\Exception $e) {
                // Ignore download errors to avoid stopping import
                // Log::error("Failed to download image: $url - " . $e->getMessage());
            }
        }
    }

    /**
     * Parse category hierarchy from string like "Hàng C>>1 Cigar C>>XG Phụ Kiện C"
     */
    protected function parseCategory(?string $categoryPath): ?int
    {
        if (empty($categoryPath)) {
            return null;
        }

        // Split by >> separator
        $categories = array_map('trim', explode('>>', $categoryPath));
        $parentId = null;

        foreach ($categories as $categoryName) {
            if (empty($categoryName)) {
                continue;
            }

            $category = Category::firstOrCreate(
                [
                    'name' => $categoryName,
                ],
                [
                    'uuid' => Str::uuid(),
                    'user_id' => auth()->id(),
                    'parent_id' => $parentId,
                ]
            );

            // Ensure parent relationship if it didn't exist properly before (optional, but good for self-healing)
            if ($category->parent_id !== $parentId && ! is_null($parentId)) {
                $category->parent_id = $parentId;
                $category->save();
            }

            $parentId = $category->id;
        }

        return $parentId;
    }

    protected function parseOrCreateTrademark(?string $name): ?int
    {
        if (empty($name)) {
            return null;
        }

        $trademark = Trademark::firstOrCreate(
            ['name' => trim($name)],
            [
                'uuid' => Str::uuid(),
                'user_id' => auth()->id(),
            ]
        );

        return $trademark->id;
    }

    protected function parseOrCreateShelve(?string $name): ?int
    {
        if (empty($name)) {
            return null;
        }

        $shelve = Shelve::firstOrCreate(
            ['name' => trim($name)],
            [
                'uuid' => Str::uuid(),
                'user_id' => auth()->id(),
            ]
        );

        return $shelve->id;
    }

    protected function parseType(?string $type): string
    {
        return match (mb_strtolower(trim($type ?? ''))) {
            'dịch vụ', 'dich vu', 'service' => 'service',
            'combo' => 'combo',
            default => 'product',
        };
    }

    protected function parseStatus($value): string
    {
        return $this->parseBoolean($value) ? 'published' : 'draft'; // Adjust based on your Status Enum
    }

    protected function parseNumber($value): int
    {
        if (is_numeric($value)) {
            return (int) $value;
        }
        // Remove commas or dots if used as thousand separators (simplified)
        $clean = preg_replace('/[.,]/', '', (string)$value);
        if (is_numeric($clean)) {
            return (int) $clean;
        }

        return 0;
    }

    protected function parseBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (bool) $value;
        }

        $stringValue = mb_strtolower(trim((string) $value));

        return in_array($stringValue, ['1', 'true', 'yes', 'có', 'co', 'đang kinh doanh']);
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function getRowCount(): int
    {
        return $this->rowCount;
    }

    public function getCreatedCount(): int
    {
        return $this->createdCount;
    }

    public function getUpdatedCount(): int
    {
        return $this->updatedCount;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
