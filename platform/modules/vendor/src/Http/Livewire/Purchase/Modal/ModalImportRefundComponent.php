<?php

namespace Polirium\Modules\Vendor\Http\Livewire\Purchase\Modal;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\On;
use Livewire\Attributes\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Polirium\Modules\Vendor\Imports\RefundImport;

class ModalImportRefundComponent extends Component
{
    use WithFileUploads;

    #[Rule(['file' => 'required|file|mimes:xlsx,xls,csv|max:10240'])]
    public $file;

    public bool $importing = false;

    public array $importResult = [];

    public function render()
    {
        return view('modules/vendor::purchase.modal.modal-import-refund');
    }

    #[On('show-modal-import-refund')]
    public function showModal()
    {
        $this->reset([
            'file',
            'importing',
            'importResult',
        ]);
        $this->dispatch('modal', 'modal-import-refund');
    }

    public function downloadTemplate()
    {
        $templatePath = base_path('platform/modules/vendor/public/PurchaseReturnImportTemplate.xlsx');

        if (file_exists($templatePath)) {
            return response()->download($templatePath, 'mau_tra_hang_nhap.xlsx');
        }

        // Return a simple CSV as fallback
        return $this->generateSimpleTemplate();
    }

    protected function generateSimpleTemplate()
    {
        $headers = [
            'Mã hàng',
            'Tên hàng',
            'Đơn vị tính',
            'Số lượng',
            'Giá nhập',
            'Giá trả lại',
            'Giảm giá trả lại',
            'Giảm giá trả lại (%)',
        ];

        $csv = implode(',', $headers);

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="mau_tra_hang_nhap.csv"',
        ]);
    }

    public function import()
    {
        $this->authorize('vendors.refunds.view');
        $this->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $this->importing = true;
        $this->importResult = [];

        try {
            $import = new RefundImport();

            Excel::import($import, $this->file->getRealPath());

            $products = $import->getProducts();

            $this->importResult = [
                'success' => true,
                'message' => "Đã đọc " . count($products) . " sản phẩm từ file.",
                'count' => count($products),
                'errors' => $import->getErrors(),
            ];

            // Dispatch products to RefundComponent
            if (count($products) > 0) {
                $this->dispatch('import-refund-products', products: $products);
                $this->dispatch('modal', 'modal-import-refund', 'hide');
            }

        } catch (\Throwable $th) {
            $this->importResult = [
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $th->getMessage(),
            ];
        }

        $this->importing = false;
    }

    public function closeModal()
    {
        $this->dispatch('modal', 'modal-import-refund', 'hide');
    }
}
