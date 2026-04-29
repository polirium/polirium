<?php

namespace Polirium\Modules\Product\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Polirium\Core\Base\Http\Controllers\BaseController;
use Polirium\Modules\PrintForms\Http\Model\Form;
use Polirium\Modules\Product\Http\Model\Payment\Payment;

class ProductController extends BaseController
{
    use AuthorizesRequests;

    public function index()
    {
        $this->authorize('products.index');

        return view('modules/product::index.index');
    }

    public function payment()
    {
        $this->authorize('sales.payment.index');

        return view('modules/product::payment.index');
    }

    public function paymentV2()
    {
        $this->authorize('sales.payment.index');

        return view('modules/product::payment.v2');
    }

    public function refund(int $id)
    {
        $this->authorize('sales.payment.refund');

        // Redirect to POS V2 with refund_id parameter to open Refund Tab
        return redirect()->route('products.payment.v2', ['refund_id' => $id]);
    }

    public function priceSetting()
    {
        $this->authorize('products.price-setting');

        return view('modules/product::price-setting.index');
    }

    public function printPayment($id)
    {
        $this->authorize('sales.print');
        $payment = Payment::with(['products.product'])->findOrFail($id);
        $form = Form::where('type', 'invoice')->where('active', 1)->first();

        if (! $form) {
            return '<div class="p-5 text-center" style="font-family: sans-serif;">
                <h2 class="text-danger">Chưa có mẫu hoá đơn</h2>
                <p>Vui lòng tạo mẫu hoá đơn (invoice) và kích hoạt trong phần <strong>Cài đặt > Mẫu in</strong></p>
            </div>';
        }

        // Tính toán chiết khấu %
        $discountPercent = $payment->discount_type === 'percent'
            ? $payment->discount_value
            : ($payment->total_cost > 0 ? round(($payment->discount_value / $payment->total_cost) * 100, 2) : 0);
            
        // Tính toán chiết khấu (số tiền thực tế)
        $actualDiscountAmount = $payment->discount_type === 'percent'
            ? ($payment->total_cost * $payment->discount_value / 100)
            : $payment->discount_value;

        $data = [
            'Ma_Don_Hang' => $payment->code,
            'Ngay' => core_format_date($payment->created_at, 'd'),
            'Thang' => core_format_date($payment->created_at, 'm'),
            'Nam' => core_format_date($payment->created_at, 'y'),
            'Khach_Hang' => $payment->customer?->name ?? 'Khách lẻ',
            'So_Dien_Thoai' => $payment->customer?->phone ?? '',
            'Dia_Chi_Khach_Hang' => $payment->customer?->address ?? '',
            'Phuong_Xa_Khach_Hang' => $payment->customer?->ward?->name ?? '',
            'Khu_Vuc_Khach_Hang_QH_TP' => trim(($payment->customer?->district?->name ?? '') . ', ' . ($payment->customer?->province?->name ?? ''), ', '),
            'Danh_Sach_Hang_Hoa' => view('modules/product::print.payment', ['products' => $payment->products])->render(),
            'Tong_Tien_Hang' => core_number_format($payment->total_cost),
            'Chiet_Khau_Hoa_Don' => core_number_format($actualDiscountAmount),
            'Chiet_Khau_Hoa_Don_Phan_Tram' => "{$discountPercent}%",
            'Tong_Cong' => core_number_format($payment->value),
            'Ghi_Chu' => $payment->note ?? '',
            // English aliases for default templates
            'code' => $payment->code,
            'day' => core_format_date($payment->created_at, 'd'),
            'month' => core_format_date($payment->created_at, 'm'),
            'year' => core_format_date($payment->created_at, 'y'),
            'full_date' => core_format_date($payment->created_at, 'date'),
            'note' => $payment->note ?? '',
            'customer_name' => $payment->customer?->name ?? 'Khách lẻ',
            'customer_phone' => $payment->customer?->phone ?? '',
            'customer_address' => $payment->customer?->address ?? '',
            'customer_ward' => $payment->customer?->ward?->name ?? '',
            'customer_district' => trim(($payment->customer?->district?->name ?? '') . ', ' . ($payment->customer?->province?->name ?? ''), ', '),
            'items_table' => view('modules/product::print.payment', ['products' => $payment->products])->render(),
            'total_amount' => core_number_format($payment->total_cost),
            'discount_amount' => core_number_format($actualDiscountAmount),
            'discount_percent' => "{$discountPercent}%",
            'grand_total' => core_number_format($payment->value),
            'customer_paid' => core_number_format($payment->value_payment),
            'change_amount' => core_number_format(max(0, $payment->value_payment - $payment->value)),
            // Store info
            'store_logo' => '<img src="' . asset('vendor/modules/product/images/xgnd.png') . '" alt="">',
            'store_name' => 'Cửa Hàng Demo',
            'store_address' => '123 Đường Demo',
            'store_phone' => '0901234567',
        ];

        try {
            // Decode any HTML entities in the template from WYSIWYG storage (e.g. &#123; -> {)
            $templateString = html_entity_decode($form->content);

            $loader = new \Twig\Loader\ArrayLoader([
                'index' => $templateString,
            ]);

            $twig = new \Twig\Environment($loader, [
                'cache' => false,
                'autoescape' => false,
            ]);

            $twig->addFunction(new \Twig\TwigFunction('__', function ($string) {
                return trans($string);
            }));

            $html = $twig->render('index', $data);

            // Trả về HTML thuần - JavaScript xử lý in sẽ ở phía client (print-helper.js)
            return $html;

        } catch (\Exception $e) {
            return '<div class="alert alert-danger">Print Error: ' . $e->getMessage() . '</div>';
        }
    }
}
