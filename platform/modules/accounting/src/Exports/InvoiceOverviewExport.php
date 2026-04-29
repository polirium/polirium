<?php

namespace Polirium\Modules\Accounting\Exports;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Polirium\Modules\Product\Http\Model\Payment\Payment;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceOverviewExport
{
    public function __construct(protected array $filters = [])
    {
    }

    public function download(string $filename): StreamedResponse
    {
        $templatePath = base_path('platform/modules/accounting/resources/templates/invoice_overview_template.xlsx');
        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();

        // Clear sample data rows (keep row 1 header)
        $lastDataRow = $sheet->getHighestRow();
        if ($lastDataRow > 1) {
            $sheet->removeRow(2, $lastDataRow - 1);
        }

        // Query data
        $payments = Payment::query()
            ->with(['customer', 'saleChannel'])
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
            $deliveryStatus = match ($payment->delivery_status ?? null) {
                'pending' => 'Chờ xử lý',
                'shipping' => 'Đang giao',
                'delivered' => 'Đã giao',
                'returned' => 'Đã trả',
                'failed' => 'Giao thất bại',
                default => $payment->delivery_status ?? '',
            };

            $sheet->setCellValue("A{$row}", $payment->code);
            $sheet->setCellValue("B{$row}", $payment->delivery_code ?? '');
            $sheet->setCellValue("C{$row}", $deliveryStatus);
            $sheet->setCellValue("D{$row}", $payment->created_at?->format('Y-m-d H:i:s'));
            $sheet->setCellValue("E{$row}", $payment->customer?->name ?? '');
            $sheet->setCellValue("F{$row}", $payment->saleChannel?->name ?? '');
            $sheet->setCellValue("G{$row}", (float) ($payment->value ?? 0));
            $sheet->setCellValue("H{$row}", (float) ($payment->cod_amount ?? 0));

            // Apply number format matching template
            $sheet->getStyle("G{$row}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("H{$row}")->getNumberFormat()->setFormatCode('#,##0');

            $row++;
        }

        // Recreate the proper KiotViet ListObject / Excel Table which defines the blue header style and stripes.
        $lastRow = max(1, $row - 1);
        $tableRange = "A1:H{$lastRow}";
        $table = new \PhpOffice\PhpSpreadsheet\Worksheet\Table($tableRange, 'InvoiceOverview');
        $tableStyle = new \PhpOffice\PhpSpreadsheet\Worksheet\Table\TableStyle();
        // The template specifically uses TableStyleMedium2 for blue headers and alternating rows
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
