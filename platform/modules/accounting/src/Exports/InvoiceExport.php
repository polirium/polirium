<?php

namespace Polirium\Modules\Accounting\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Polirium\Modules\Product\Http\Model\Payment\Payment;

class InvoiceExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(protected int $paymentId)
    {
    }

    public function collection()
    {
        $payment = Payment::with(['products.product'])->findOrFail($this->paymentId);

        return $payment->products;
    }

    public function map($row): array
    {
        return [
            $row->product->code,
            $row->product->name,
            $row->amount,
            $row->price,
            $row->discount_value,
            $row->total, // Assuming 'total' is the line total
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
