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
        // Same rule as the invoice list: only invoices of a branch the user works in.
        abort_unless(user_can_access_branch($payment->branch_id), 404);

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
        // There is no separate return page: show the return in the return list.
        // (refund_id on the POS screen is an invoice id, not a return id.)
        $code = \Polirium\Modules\Product\Http\Model\Refund\Refund::whereKey($id)->value('code');

        return redirect()->route('accountings.payment.refund', $code ? ['search' => $code] : []);
    }

    public function paymentIndex()
    {
        // $this->authorize('accountings.payments');
        return view('modules/accounting::payment.index');
    }

    public function exportInvoice($id)
    {
        $branchId = \Polirium\Modules\Product\Http\Model\Payment\Payment::whereKey($id)->value('branch_id');
        abort_unless($branchId && user_can_access_branch($branchId), 404);

        return \Maatwebsite\Excel\Facades\Excel::download(new \Polirium\Modules\Accounting\Exports\InvoiceExport($id), 'hoa_don_' . $id . '.xlsx');
    }

    public function copyInvoice($id)
    {
        // Logic to duplicate invoice
        $original = \Polirium\Modules\Product\Http\Model\Payment\Payment::with('products')->findOrFail($id);
        abort_unless(user_can_access_branch($original->branch_id), 404);

        // Clone Payment
        $newPayment = $original->replicate(['uuid', 'code', 'created_at', 'updated_at']);
        $newPayment->code = code_generate('HD', \Polirium\Modules\Product\Http\Model\Payment\Payment::max('id'));
        $newPayment->status = 'temp'; // Set as temp/draft
        $newPayment->completed_at = null;
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
