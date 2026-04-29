<?php

namespace Polirium\Modules\Customer\Http\Livewire\Index\Modal;

use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use Polirium\Modules\Customer\Imports\CustomerImport;

class ModalImportCustomerComponent extends Component
{
    use WithFileUploads;

    public $file;
    public array $importErrors = [];
    public int $importedCount = 0;
    public int $updatedCount = 0;
    public bool $hasResult = false;

    protected function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'], // Max 10MB
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'file' => 'file Excel',
        ];
    }

    #[On('show-modal-import-customer')]
    public function openModal(): void
    {
        $this->reset('file', 'importErrors', 'importedCount', 'updatedCount', 'hasResult');
        $this->dispatch('modal', 'modal-import-customer');
    }

    public function closeModal(): void
    {
        $this->dispatch('modal', 'modal-import-customer', 'hide');
    }

    public function import(): void
    {
        $this->authorize('customers.create');

        $this->validate();

        try {
            // Tăng timeout cho import lớn
            set_time_limit(0); // Unlimited execution time
            ini_set('memory_limit', '512M');

            $import = new CustomerImport();
            Excel::import($import, $this->file->getRealPath());

            $imported = $import->getImported();
            $updated = $import->getUpdated();
            $errors = $import->getErrors();

            $this->importedCount = is_countable($imported) ? count($imported) : 0;
            $this->updatedCount = is_countable($updated) ? count($updated) : 0;
            $this->importErrors = $errors;
            $this->hasResult = true;

            if (empty($errors)) {
                $this->dispatch('success', "Nhập thành công {$this->importedCount} khách hàng mới, cập nhật {$this->updatedCount} khách hàng.");
                $this->dispatch('refresh-datatable-customers');
                // Không đóng modal ngay, để user thấy kết quả
            }
        } catch (\Throwable $th) {
            $this->importErrors[] = 'Lỗi: ' . $th->getMessage();
            $this->hasResult = true;
        }
    }

    public function downloadTemplate(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $headers = [
            'Loại khách',
            'Chi nhánh tạo',
            'Mã khách hàng (Để trống tự tạo KH/00001)',
            'Tên khách hàng',
            'Điện thoại',
            'Địa chỉ',
            'Khu vực giao hàng',
            'Phường/Xã',
            'Công ty',
            'Mã số thuế',
            'Số CMND/CCCD',
            'Ngày sinh',
            'Giới tính',
            'Email',
            'Facebook',
            'Nhóm khách hàng',
            'Ghi chú',
            'Người tạo',
            'Ngày tạo',
            'Ngày giao dịch cuối',
            'Nợ cần thu hiện tại',
            'Tổng bán',
            'Tổng bán trừ trả hàng',
            'Trạng thái',
        ];

        $filename = 'mau_nhap_khach_hang.xlsx';

        return Excel::download(new class ($headers) implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithHeadings {
            public function __construct(private array $headers)
            {
            }
            public function array(): array
            {
                return [];
            }
            public function headings(): array
            {
                return $this->headers;
            }
        }, $filename);
    }

    public function render()
    {
        return view('modules/customer::index.modal.import');
    }
}
