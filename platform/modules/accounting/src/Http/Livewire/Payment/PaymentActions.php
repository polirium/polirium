<?php

namespace Polirium\Modules\Accounting\Http\Livewire\Payment;

use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Polirium\Modules\Product\Http\Model\Payment\Payment;
use Polirium\Modules\Product\Http\Support\PaymentInventorySupport;
use Polirium\Modules\Product\Http\Support\ProductInventorySupport;

class PaymentActions extends Component
{
    public int $paymentId;
    public $status; // Store status to conditionally show buttons
    public $value = 0;
    public $valuePayment = 0;

    public $completedAt;

    public function mount($payment)
    {
        $this->paymentId = is_object($payment) ? $payment->id : $payment['id'];
        $this->status = is_object($payment) ? $payment->status : ($payment['status'] ?? '');
        $this->value = is_object($payment) ? $payment->value : ($payment['value'] ?? 0);
        $this->valuePayment = is_object($payment) ? $payment->value_payment : ($payment['value_payment'] ?? 0);
        $this->completedAt = is_object($payment) ? $payment->completed_at : ($payment['completed_at'] ?? null);
    }

    public function cancel()
    {
        try {
            if (! auth()->user()->can('accountings.cancel')) {
                $this->dispatch('error', 'Bạn không có quyền hủy hóa đơn.');

                return;
            }

            $payment = Payment::with('products')->find($this->paymentId);

            if (! $payment) {
                $this->dispatch('error', 'Không tìm thấy hóa đơn.');

                return;
            }

            if (in_array($payment->status, ['cancelled', 'cancel', 'failed'])) {
                $this->dispatch('error', 'Hóa đơn đã bị hủy trước đó.');

                return;
            }

            $restored = \Illuminate\Support\Facades\DB::transaction(function () use ($payment) {
                $restored = PaymentInventorySupport::restoreExportedStock($payment);

                // Update Status
                $payment->status = 'cancel';
                $payment->save();

                if ($payment->finance) {
                    $payment->finance->status = 'cancelled';
                    $payment->finance->save();
                }

                return $restored;
            });

            $this->status = 'cancel'; // Update local status
            $this->dispatch(
                'success',
                $restored > 0
                    ? 'Đã hủy hóa đơn và hoàn lại tồn kho đã xuất.'
                    : 'Đã hủy hóa đơn tạm; tồn kho không thay đổi.'
            );

            // Refresh the parent table to update status badge
            $this->dispatch('refresh-datatable-product-payments');
            $this->dispatch('pg:eventRefresh-product-payment-table');
        } catch (\Exception $e) {
            Log::error('PaymentActions cancel error: ' . $e->getMessage());
            $this->dispatch('error', 'Lỗi hệ thống: ' . $e->getMessage());
        }
    }

    public function markAsDeliveryFailed()
    {
        try {
            if (! auth()->user()->can('accountings.cancel')) {
                $this->dispatch('error', 'Bạn không có quyền thực hiện thao tác này.');

                return;
            }

            $payment = Payment::with('products')->find($this->paymentId);

            if (! $payment) {
                $this->dispatch('error', 'Không tìm thấy hóa đơn.');

                return;
            }

            if (in_array($payment->status, ['cancelled', 'cancel', 'failed', 'delivery_failed'])) {
                $this->dispatch('error', 'Hóa đơn đã ở trạng thái không thể thay đổi.');

                return;
            }

            $restored = \Illuminate\Support\Facades\DB::transaction(function () use ($payment) {
                $restored = PaymentInventorySupport::restoreExportedStock($payment);

                // Update Status
                $payment->status = 'delivery_failed';
                $payment->save();

                if ($payment->finance) {
                    $payment->finance->status = 'cancelled';
                    $payment->finance->save();
                }

                return $restored;
            });

            $this->status = 'delivery_failed';
            $this->dispatch(
                'success',
                $restored > 0
                    ? 'Đã đánh dấu không giao được và hoàn lại tồn kho đã xuất.'
                    : 'Đã đánh dấu không giao được; tồn kho không thay đổi.'
            );

            $this->dispatch('refresh-datatable-product-payments');
            $this->dispatch('pg:eventRefresh-product-payment-table');
        } catch (\Exception $e) {
            Log::error('PaymentActions markAsDeliveryFailed error: ' . $e->getMessage());
            $this->dispatch('error', 'Lỗi hệ thống: ' . $e->getMessage());
        }
    }

    public function approve()
    {
        try {
            if (! auth()->user()->can('accountings.invoices')) { // Assuming generic permission or add new
                $this->dispatch('error', 'Bạn không có quyền duyệt hóa đơn.');

                return;
            }

            $payment = Payment::with('products')->find($this->paymentId);

            if (! $payment) {
                $this->dispatch('error', 'Không tìm thấy hóa đơn.');

                return;
            }

            if ($payment->status !== 'temp') {
                $this->dispatch('error', 'Chỉ có thể duyệt hóa đơn nháp.');

                return;
            }

            \Illuminate\Support\Facades\DB::transaction(function () use ($payment) {
                // Deduct Stock
                ProductInventorySupport::exportPaymentItems($payment->products, $payment);

                // Update Status
                $payment->status = 'success';
                $payment->completed_at = now();
                $payment->save();

                if ($payment->finance) {
                    $payment->finance->status = 'completed'; // or whatever standard status is
                    $payment->finance->save();
                }
            });

            $this->status = 'success';
            $this->dispatch('success', 'Đã duyệt hóa đơn thành công.');

            $this->dispatch('refresh-datatable-product-payments');
            $this->dispatch('pg:eventRefresh-product-payment-table');
        } catch (\Exception $e) {
            Log::error('PaymentActions approve error: ' . $e->getMessage());
            $this->dispatch('error', 'Lỗi hệ thống: ' . $e->getMessage());
        }
    }

    public function complete()
    {
        try {
            if (! auth()->user()->can('accountings.edit')) {
                $this->dispatch('error', 'Bạn không có quyền cập nhật hóa đơn.');

                return;
            }

            $payment = Payment::find($this->paymentId);

            if (! $payment) {
                $this->dispatch('error', 'Không tìm thấy hóa đơn.');

                return;
            }

            \Illuminate\Support\Facades\DB::transaction(function () use ($payment) {
                // Unified Complete Action: Status Success + Fully Paid + Completed Timestamp
                $payment->status = 'success';
                $payment->value_payment = $payment->value; // Thu đủ tiền
                $payment->completed_at = now();

                // Update type_payment value if needed
                $typePayment = $payment->type_payment ?? [];
                if (is_array($typePayment) && count($typePayment) > 0) {
                    $typePayment[0]['value'] = $payment->value;
                    $payment->type_payment = $typePayment;
                }

                $payment->save();

                if ($payment->finance) {
                    $payment->finance->status = 'completed';
                    $payment->finance->save();
                }
            });

            $this->status = 'success';
            $this->completedAt = $payment->completed_at;
            $this->valuePayment = $payment->value;

            $this->dispatch('success', 'Đã hoàn thành đơn hàng.');

            $this->dispatch('refresh-datatable-product-payments');
            $this->dispatch('pg:eventRefresh-product-payment-table');
        } catch (\Exception $e) {
            Log::error('PaymentActions complete error: ' . $e->getMessage());
            $this->dispatch('error', 'Lỗi hệ thống: ' . $e->getMessage());
        }
    }

    public function copyInvoice()
    {
        $this->authorize('accountings.create');

        try {
            $original = Payment::with('products')->find($this->paymentId);

            if (! $original) {
                $this->dispatch('error', 'Không tìm thấy hóa đơn cần sao chép.');

                return;
            }

            $newPayment = \Illuminate\Support\Facades\DB::transaction(function () use ($original) {
                // Clone Payment as temp/draft
                $newPayment = $original->replicate(['uuid', 'code', 'created_at', 'updated_at']);
                $newPayment->code = code_generate('HD', Payment::max('id'));
                $newPayment->status = 'temp';
                $newPayment->completed_at = null;
                $newPayment->created_at = now();
                $newPayment->save();

                // Clone Products
                foreach ($original->products as $product) {
                    $newProduct = $product->replicate(['payment_id', 'created_at', 'updated_at']);
                    $newProduct->product_payment_id = $newPayment->id;
                    $newProduct->save();
                }

                return $newPayment;
            });

            $this->dispatch('success', 'Đã tạo bản sao hóa đơn nháp. Đang mở chi tiết...');
            $this->dispatch('refresh-datatable-product-payments');
            $this->dispatch('pg:eventRefresh-product-payment-table');

            // Open the edit invoice modal with the copied ID
            $this->dispatch('show-modal-create-sale-invoice', id: $newPayment->id);

        } catch (\Exception $e) {
            Log::error('PaymentActions copy error: ' . $e->getMessage());
            $this->dispatch('error', 'Lỗi hệ thống: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('modules/accounting::payment.actions');
    }

}
