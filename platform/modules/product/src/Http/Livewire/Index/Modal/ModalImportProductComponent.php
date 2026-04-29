<?php

namespace Polirium\Modules\Product\Http\Livewire\Index\Modal;

use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use Polirium\Modules\Product\Imports\ProductImport;

class ModalImportProductComponent extends Component
{
    use WithFileUploads;

    #[Rule(['file' => 'required|file|mimes:xlsx,xls,csv|max:10240'])]
    public $file;

    // Xử lý trùng mã hàng/mã vạch, khác tên hàng hóa
    public string $duplicate_code_handling = 'error'; // 'error' | 'replace_name'

    // Xử lý trùng mã vạch, khác mã hàng
    public string $duplicate_barcode_handling = 'error'; // 'error' | 'replace_code'

    // Áp dụng giá vốn khi thêm mới, cập nhật sản phẩm
    public string $cost_price_scope = 'global'; // 'global' | 'branch'
    public ?int $cost_price_branch_id = null;

    // Trạng thái kinh doanh áp dụng
    public string $business_status_scope = 'global'; // 'global' | 'branch'
    public ?int $business_status_branch_id = null;

    // Cập nhật tồn kho
    public $update_stock = 0;

    // Cập nhật mô tả
    public $update_description = 0;

    // Cập nhật giá vốn
    public $update_cost_price = 0;

    public array $branches = [];

    public bool $importing = false;

    public array $importResult = [];

    public function mount()
    {
        $this->branches = \Polirium\Core\Base\Http\Models\Branch\Branch::select(['id', 'name'])
            ->pluck('name', 'id')
            ->all() ?? [];
    }

    public function render()
    {
        return view('modules/product::index.modal.modal-import-product');
    }

    #[On('show-modal-import-product')]
    public function showModal()
    {
        $this->reset([
            'file',
            'importing',
            'importResult',
        ]);
        $this->dispatch('modal', 'modal-import-product');
    }

    public function downloadTemplate()
    {
        $templatePath = base_path('platform/modules/product/public/sample_import_products.xlsx');

        if (file_exists($templatePath)) {
            return response()->download($templatePath, 'mau_nhap_hang_hoa.xlsx');
        }

        return $this->generateSimpleTemplate();
    }

    protected function generateSimpleTemplate()
    {
        $headers = [
            'Loại hàng', 'Nhóm hàng(3 Cấp)', 'Mã hàng', 'Tên hàng', 'Thương hiệu',
            'Giá bán', 'Giá vốn', 'Tồn kho', 'KH đặt', 'Dự kiến hết hàng',
            'Tồn nhỏ nhất', 'Tồn lớn nhất', 'ĐVT', 'Mã ĐVT Cơ bản', 'Quy đổi',
            'Thuộc tính', 'Mã HH Liên quan', 'Hình ảnh (url1,url2...)', 'Trọng lượng',
            'Đang kinh doanh', 'Được bán trực tiếp', 'Mô tả', 'Mẫu ghi chú',
            'Vị trí', 'Hàng thành phần', 'Thời gian tạo',
        ];

        $csv = implode(',', array_map(fn ($v) => '"' . str_replace('"', '""', $v) . '"', $headers));

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="mau_nhap_hang_hoa.csv"',
        ]);
    }

    public function import()
    {
        $this->authorize('products.create');

        $this->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $this->importing = true;
        $this->importResult = [];

        DB::beginTransaction();

        try {
            $import = new ProductImport(
                duplicateCodeHandling: $this->duplicate_code_handling,
                duplicateBarcodeHandling: $this->duplicate_barcode_handling,
                costPriceScope: $this->cost_price_scope,
                costPriceBranchId: $this->cost_price_branch_id,
                businessStatusScope: $this->business_status_scope,
                businessStatusBranchId: $this->business_status_branch_id,
                updateStock: (bool) $this->update_stock,
                updateDescription: (bool) $this->update_description,
                updateCostPrice: (bool) $this->update_cost_price,
            );

            Excel::import($import, $this->file->getRealPath());

            DB::commit();

            $this->importResult = [
                'success' => true,
                'message' => "Import thành công! Đã import {$import->getRowCount()} sản phẩm.",
                'created' => $import->getCreatedCount(),
                'updated' => $import->getUpdatedCount(),
                'errors' => $import->getErrors(),
            ];

            $this->dispatch('refresh-datatable-products');

        } catch (\Throwable $th) {
            DB::rollBack();

            $this->importResult = [
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $th->getMessage(),
            ];
        }

        $this->importing = false;
    }

    public function closeModal()
    {
        $this->dispatch('modal', 'modal-import-product', 'hide');
    }
}
