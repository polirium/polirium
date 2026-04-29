<?php

namespace Polirium\Modules\Vendor\Http\Controllers;

use Polirium\Core\Base\Http\Controllers\BaseController;
use Illuminate\Http\Request;

class PurchaseController extends BaseController
{
    public function index()
    {
        $this->authorize('vendors.purchases.index');
        page_title()->setTitle(__('Phiếu nhập hàng'));

        return view('modules/vendor::purchase.index.index');
    }

    public function order(int $id = 0)
    {
        $this->authorize('vendors.purchases.create');
        page_title()->setTitle($id ? __('Sửa phiếu nhập') : __('Tạo phiếu nhập'));

        return view('modules/vendor::purchase.order.index', compact('id'));
    }

    public function show(int $id)
    {
        $this->authorize('vendors.purchases.index');
        $purchase = \Polirium\Modules\Vendor\Http\Model\Purchase\Purchase::with(['vendor', 'branch', 'userCreated', 'products.product'])->findOrFail($id);
        page_title()->setTitle(__('Chi tiết phiếu nhập #:code', ['code' => $purchase->code]));

        return view('modules/vendor::purchase.show', compact('purchase'));
    }

    public function listRefund()
    {
        $this->authorize('vendors.refunds.index');
        page_title()->setTitle(__('Danh sách trả hàng nhập'));

        return view('modules/vendor::purchase.refund.table');
    }

    public function refund($id = null)
    {
        $this->authorize('vendors.refunds.index');
        page_title()->setTitle($id ? __('Sửa trả hàng nhập') : __('Tạo trả hàng nhập'));

        return view('modules/vendor::purchase.refund.index', compact('id'));
    }

    public function export($id)
    {
        // $this->authorize('vendors.purchases.export'); // Assuming generic perm or index for now
        return \Maatwebsite\Excel\Facades\Excel::download(new \Polirium\Modules\Vendor\Exports\PurchaseExport($id), 'phieu_nhap_' . $id . '.xlsx');
    }
}
