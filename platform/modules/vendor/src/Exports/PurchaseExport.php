<?php

namespace Polirium\Modules\Vendor\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Polirium\Modules\Vendor\Http\Model\Purchase\Purchase;

class PurchaseExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(protected int $purchaseId) {}

    public function collection()
    {
        $purchase = Purchase::with(['products.product'])->findOrFail($this->purchaseId);
        return $purchase->products;
    }

    public function map($row): array
    {
        return [
            $row->product->code,
            $row->product->name,
            $row->amount,
            $row->price,
            $row->discount_value,
            $row->value,
        ];
    }

    public function headings(): array
    {
        return [
            'Mã hàng',
            'Tên hàng',
            'Số lượng',
            'Đơn giá',
            'Giảm giá',
            'Thành tiền',
        ];
    }
}
