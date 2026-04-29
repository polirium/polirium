<?php

namespace Polirium\Modules\Accounting\Http\Controllers;

use Polirium\Core\Base\Http\Controllers\BaseController;

class AccountingController extends BaseController
{
    public function index()
    {
        // $this->authorize('accountings.index');
        return view('modules/accounting::index.index');
    }

    public function show($id)
    {
        // $this->authorize('accountings.payments');
        $payment = \Polirium\Modules\Product\Http\Model\Payment\Payment::findOrFail($id);

        return view('modules/accounting::payment.show', compact('payment'));
    }

    public function invoice()
    {
        // $this->authorize('accountings.invoices');
        return view('modules/accounting::invoice.index');
    }

    public function paymentRefund()
    {
        // $this->authorize('accountings.refunds');
        return view('modules/accounting::payment.refund.index');
    }

    public function paymentRefundDetail($id)
    {
        // $this->authorize('accountings.refunds');
        // Redirect to POS V2 integration
        return redirect()->route('products.payment.v2', ['refund_id' => $id]);
    }

    public function paymentIndex()
    {
        // $this->authorize('accountings.payments');
        return view('modules/accounting::payment.index');
    }

    public function exportInvoice($id)
    {
        return \Maatwebsite\Excel\Facades\Excel::download(new \Polirium\Modules\Accounting\Exports\InvoiceExport($id), 'hoa_don_' . $id . '.xlsx');
    }

    public function copyInvoice($id)
    {
        // Logic to duplicate invoice
        $original = \Polirium\Modules\Product\Http\Model\Payment\Payment::with('products')->findOrFail($id);

        // Clone Payment
        $newPayment = $original->replicate(['uuid', 'code', 'created_at', 'updated_at']);
        $newPayment->code = code_generate('HD', \Polirium\Modules\Product\Http\Model\Payment\Payment::max('id'));
        $newPayment->status = 'temp'; // Set as temp/draft
        $newPayment->created_at = now();
        $newPayment->save();

        // Clone Products
        foreach ($original->products as $product) {
            $newProduct = $product->replicate(['payment_id', 'created_at', 'updated_at']); // Check fk name
            // 'product_payment_products' usually has 'product_payment_id' or 'payment_id' depending on model.
            // Let's check PaymentProduct model.
            // PaymentProduct belongsTo Payment via 'product_payment_id'.
            $newProduct->product_payment_id = $newPayment->id;
            $newProduct->save();
        }

        return redirect()->route('accountings.payment.index')->with('success', 'Đã sao chép hóa đơn thành công! Mã mới: ' . $newPayment->code);
    }

    public function salesReport()
    {
        return view('modules/accounting::report.sales');
    }
}
