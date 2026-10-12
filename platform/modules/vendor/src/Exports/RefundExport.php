<?php

namespace Polirium\Modules\Vendor\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Polirium\Modules\Vendor\Http\Model\Refund\Refund;

class RefundExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(protected Refund $refund, protected bool $canViewPrice) {}

    public function collection()
    {
        return $this->refund->products;
    }

    public function map($row): array
    {
        $values = [$row->product?->code ?? '', $row->product?->name ?? '', $row->product?->unit ?? '', $row->amount];
        if ($this->canViewPrice) {
            $values = array_merge($values, [$row->price, $row->value]);
        }
        return array_merge($values, [$row->note]);
    }

    public function headings(): array
    {
        $headings = ['Mã hàng', 'Tên hàng', 'Đơn vị tính', 'Số lượng'];
        if ($this->canViewPrice) {
            $headings = array_merge($headings, ['Giá trả lại', 'Thành tiền']);
        }
        return array_merge($headings, ['Ghi chú']);
    }
}
