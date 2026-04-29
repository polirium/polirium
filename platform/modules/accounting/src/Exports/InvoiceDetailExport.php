<?php

namespace Polirium\Modules\Accounting\Exports;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Polirium\Modules\Product\Http\Model\Payment\Payment;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceDetailExport
{
    public function __construct(protected array $filters = [])
    {
    }

    public function download(string $filename): StreamedResponse
    {
        $templatePath = base_path('platform/modules/accounting/resources/templates/invoice_detail_template.xlsx');
        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();

        // Capture number formats from template row 2 before clearing
        $numberFormatMap = [];
        for ($col = 1; $col <= 59; $col++) {
            $cell = $sheet->getCellByColumnAndRow($col, 2);
            $fmt = $cell->getStyle()->getNumberFormat()->getFormatCode();
            if ($fmt && $fmt !== 'General') {
                $numberFormatMap[$col] = $fmt;
            }
        }

        // Clear sample data rows (keep row 1 header)
        $lastDataRow = $sheet->getHighestRow();
        if ($lastDataRow > 1) {
            $sheet->removeRow(2, $lastDataRow - 1);
        }

        $payments = Payment::query()
            ->with(['customer', 'saleChannel', 'branch', 'user', 'products.product', 'latestDelivery.partnerDelivery'])
            ->when(user_branch(), fn ($q) => $q->where('branch_id', user_branch()))
            ->when($this->filters['date'] ?? null, function ($q, $date) {
                $dates = explode(' to ', $date);
                if (count($dates) === 2) {
                    $q->whereBetween('created_at', [$dates[0] . ' 00:00:00', $dates[1] . ' 23:59:59']);
                } else {
                    $q->whereDate('created_at', $date);
                }
            })
            ->when($this->filters['status_checked'] ?? null, function ($q, $statuses) {
                $q->whereIn('status', $statuses);
            })
            ->orderByDesc('id')
            ->get();

        $row = 2;
        foreach ($payments as $payment) {
            $statusName = match ($payment->status ?? '') {
                'success' => 'Đang xử lý',
                'completed' => 'Hoàn thành',
                'temp' => 'Nháp',
                'cancel', 'cancelled' => 'Đã hủy',
                'delivery_failed' => 'Giao thất bại',
                default => $payment->status ?? '',
            };

            $deliveryStatus = match ($payment->delivery_status ?? null) {
                'pending' => 'Chờ xử lý',
                'shipping' => 'Đang giao',
                'delivered' => 'Đã giao',
                'returned' => 'Đã trả',
                'failed' => 'Giao thất bại',
                default => $payment->delivery_status ?? '',
            };

            // Parse type_payment
            $typePayments = is_array($payment->type_payment) ? $payment->type_payment : [];
            $cashAmount = 0;
            $cardAmount = 0;
            $walletAmount = 0;
            $bankAmount = 0;

            foreach ($typePayments as $tp) {
                $method = $tp['method'] ?? '';
                $val = (float) ($tp['value'] ?? 0);
                match ($method) {
                    'cash' => $cashAmount += $val,
                    'card' => $cardAmount += $val,
                    'wallet', 'other' => $walletAmount += $val,
                    'bank' => $bankAmount += $val,
                    default => null,
                };
            }

            $note = $payment->note ?? '';

            foreach ($payment->products as $item) {
                $discountPercent = ($item->discount_type === 'percent') ? (float) ($item->discount_value ?? 0) : 0;
                $discountNumber = ($item->discount_type === 'number') ? (float) ($item->discount_value ?? 0) : 0;
                $qty = (int) ($item->amount ?? 0);
                $price = (float) ($item->price ?? 0);
                $lineTotal = ($qty * $price) - (($item->discount_type === 'number') ? $discountNumber : ($qty * $price * $discountPercent / 100));

                // Col mapping: A=1 .. BG=59
                $data = [
                    1 => $payment->branch?->name ?? '',
                    2 => $payment->code,
                    3 => $payment->delivery_code ?? '',
                    4 => '',
                    5 => '',
                    6 => 0,
                    7 => $payment->created_at?->format('Y-m-d H:i:s'),
                    8 => $payment->created_at?->format('Y-m-d H:i:s'),
                    9 => $payment->updated_at?->format('d/m/Y H:i'),
                    10 => '',
                    11 => $payment->customer?->code ?? '',
                    12 => $payment->customer?->name ?? '',
                    13 => $payment->customer?->email ?? '',
                    14 => $payment->customer?->phone ?? '',
                    15 => $payment->customer?->address ?? '',
                    16 => '',
                    17 => '',
                    18 => '',
                    19 => '',
                    20 => $payment->user?->name ?? '',
                    21 => $payment->saleChannel?->name ?? '',
                    22 => $payment->user?->name ?? '',
                    23 => $payment->latestDelivery?->partnerDelivery?->name ?? '',
                    24 => $payment->receiver_name ?? '',
                    25 => $payment->receiver_phone ?? '',
                    26 => $payment->receiver_address ?? '',
                    27 => '',
                    28 => '',
                    29 => '',
                    30 => '',
                    31 => '',
                    32 => '',
                    33 => '',
                    34 => '',
                    35 => '',
                    36 => $note,
                    37 => (float) ($payment->total_cost ?? 0),
                    38 => (float) ($payment->discount_value ?? 0),
                    39 => (float) ($payment->value ?? 0),
                    40 => (float) ($payment->value_payment ?? 0),
                    41 => $cashAmount,
                    42 => $cardAmount,
                    43 => $walletAmount,
                    44 => $bankAmount,
                    45 => (float) ($payment->cod_amount ?? 0),
                    46 => '',
                    47 => $statusName,
                    48 => $deliveryStatus,
                    49 => $item->product?->code ?? '',
                    50 => $item->product?->name ?? '',
                    51 => '',
                    52 => $item->product?->unit ?? '',
                    53 => $item->product?->description ?? '',
                    54 => $qty,
                    55 => $price,
                    56 => $discountPercent,
                    57 => $discountNumber,
                    58 => $price,
                    59 => $lineTotal,
                ];

                foreach ($data as $col => $value) {
                    $sheet->setCellValueByColumnAndRow($col, $row, $value);

                    // Apply number format from template
                    if (isset($numberFormatMap[$col])) {
                        $sheet->getStyleByColumnAndRow($col, $row)
                            ->getNumberFormat()
                            ->setFormatCode($numberFormatMap[$col]);
                    }
                }

                $row++;
            }
        }

        // Recreate the proper KiotViet ListObject / Excel Table which defines the blue header style and stripes.
        $lastRow = max(1, $row - 1);
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(59);
        $tableRange = "A1:{$lastCol}{$lastRow}";
        $table = new \PhpOffice\PhpSpreadsheet\Worksheet\Table($tableRange, 'InvoiceDetail');
        $tableStyle = new \PhpOffice\PhpSpreadsheet\Worksheet\Table\TableStyle();
        $tableStyle->setTheme(\PhpOffice\PhpSpreadsheet\Worksheet\Table\TableStyle::TABLE_STYLE_MEDIUM2);
        $tableStyle->setShowRowStripes(true);
        $table->setStyle($tableStyle);
        $sheet->addTable($table);

        $writer = new Xlsx($spreadsheet);

        return new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
