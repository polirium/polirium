<?php

namespace Polirium\Modules\Product\Http\Livewire\Refund;

use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Rule;
use Livewire\Component;

use Polirium\Modules\Product\Http\Model\Payment\Payment;
use Polirium\Modules\Product\Http\Model\Refund\Refund;
use Polirium\Modules\Product\Http\Model\Refund\RefundProduct;

class RefundComponent extends Component
{
    public int $payment_id = 0;
    public string $tab_id = '';

    // Thay vì dùng Eloquent Model, dùng array để Livewire persist đúng cách
    public array $refundData = [];
    public float $total_after_product_discount = 0;
    public float $total_after_refund_discount = 0;
    public bool $isFinished = false;

    #[Rule([
        'products' => ['required', 'array'],
        'products.*.product_id' => ['required', 'integer'],
        'products.*.amount' => ['required', 'integer', 'min:1'],
        'products.*.price' => ['required', 'numeric', 'min:0'],
        'products.*.discount_value' => ['nullable', 'numeric', 'min:0'],
        'products.*.discount_type' => ['nullable', 'string', 'in:percent,number'],
        'products.*.value' => ['required', 'numeric', 'min:0'],
    ])]
    public array $products = [];

    protected function rules(): array
    {
        return [
            'refundData.code' => ['required', 'string', 'max:191'],
            'refundData.product_payment_id' => ['required', 'integer', 'exists:product_payments,id'],
            'refundData.amount' => ['nullable', 'integer', 'min:0'],
            'refundData.total_payable_amount' => ['nullable', 'numeric', 'min:0'],
            'refundData.original_total_amount' => ['nullable', 'numeric', 'min:0'],
            'refundData.value' => ['nullable', 'numeric', 'min:0'],
            'refundData.discount_value' => ['nullable', 'numeric', 'min:0'],
            'refundData.discount_type' => ['nullable', 'string', 'in:percent,number'],
            'refundData.refund_fee' => ['nullable', 'numeric', 'min:0'],
            'refundData.refund_fee_type' => ['nullable', 'string', 'in:percent,number'],
            'refundData.user_id' => ['required', 'integer', 'exists:users,id'],
            'refundData.user_created_id' => ['required', 'integer', 'exists:users,id'],
            'refundData.note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function mount(): void
    {
        \Log::info('RefundComponent::mount()', ['payment_id' => $this->payment_id, 'tab_id' => $this->tab_id]);
        $this->initRefundData();
        $this->loadPayment();
    }

    protected function initRefundData(): void
    {
        $this->refundData = [
            'product_payment_id' => $this->payment_id,
            'code' => code_generate('TRH', Refund::max('id') + 1),
            'discount_value' => 0,
            'discount_type' => 'number',
            'refund_fee' => 0,
            'refund_fee_type' => 'number',
            'user_id' => auth()->id(),
            'user_created_id' => auth()->id(),
            'note' => '',
            'amount' => 0,
            'total_payable_amount' => 0,
            'original_total_amount' => 0,
            'value' => 0,
        ];
    }

    #[Computed]
    public function payment(): ?Payment
    {
        return Payment::with('products.product', 'customer')->find($this->payment_id);
    }

    // Refund accessor để view có thể dùng $refund->... như cũ
    #[Computed]
    public function refund(): object
    {
        return (object) $this->refundData;
    }

    public function resetInput(): void
    {
        $this->reset('products');
        $this->isFinished = false;
        $this->initRefundData();
        $this->loadPayment();
    }

    public function loadPayment(): void
    {
        $payment = Payment::with('products.product')->findOrFail($this->payment_id);

        // Set payment cho refund - ĐẢM BẢO product_payment_id được gán
        $this->refundData['product_payment_id'] = $this->payment_id;
        $this->refundData['code'] = code_generate('TRH', Refund::max('id') + 1);
        $this->refundData['original_total_amount'] = $payment->value;

        // Load products từ payment
        $this->products = [];
        foreach ($payment->products as $item) {
            $this->products[$item->product_id] = [
                'product_id' => $item->product_id,
                'sold_amount' => (int)$item->amount,
                'amount' => 1,
                'price' => (float)$item->value,
                'discount_value' => 0,
                'discount_type' => 'number',
                'value' => (float)$item->value,
                'product' => $item->product?->only(['id', 'name', 'code', 'unit', 'price', 'cost']) ?? [],
            ];
        }

        $this->updatedRefundData();
        \Log::info('RefundComponent::loadPayment() completed', [
            'payment_id' => $this->payment_id,
            'refundData.product_payment_id' => $this->refundData['product_payment_id'],
            'products_count' => count($this->products),
        ]);
    }

    public function updatedProducts($value, $key): void
    {
        if ($this->isFinished) {
            return;
        }

        [$id, $col] = explode('.', $key);
        $id = (int)$id;

        if ($col === 'amount') {
            if ((int)$value <= 0) {
                $this->products[$id]['amount'] = 1;
            }

            $sold = (int)($this->products[$id]['sold_amount'] ?? 0);
            if ($sold > 0 && (int)$this->products[$id]['amount'] > $sold) {
                $this->products[$id]['amount'] = $sold;
            }
        }

        if ($col === 'discount_value') {
            if ((float)$value < 0) {
                $this->products[$id]['discount_value'] = 0;
            }
        }

        if ($col === 'value') {
            $price = (float)$this->products[$id]['price'] * (int)$this->products[$id]['amount'];
            $currentValue = (float)$this->products[$id]['value'];

            if ($currentValue >= $price) {
                $this->products[$id]['discount_type'] = 'number';
                $this->products[$id]['discount_value'] = 0;
            } else {
                $discountAmount = $price - $currentValue;
                $this->products[$id]['discount_type'] = 'number';
                $this->products[$id]['discount_value'] = $discountAmount;
            }
        } else {
            $price = (float)$this->products[$id]['price'] * (int)$this->products[$id]['amount'];
            $discount = discount_value($price, (float)$this->products[$id]['discount_value'], $this->products[$id]['discount_type']);
            $this->products[$id]['value'] = $discount;
        }

        $this->updatedRefundData();
    }

    public function updatedRefundData(): void
    {
        // Tổng sau giảm SP
        $totalAfterProductDiscount = array_sum(array_column($this->products, 'value'));
        $this->total_after_product_discount = $totalAfterProductDiscount;

        // Cập nhật số lượng và tổng sau giảm SP
        $this->refundData['amount'] = collect($this->products)->sum('amount');
        $this->refundData['total_payable_amount'] = (int)($totalAfterProductDiscount * 100);

        // Tính tổng phải trả (sau giảm giá refund)
        $discountValue = (float)($this->refundData['discount_value'] ?? 0);
        $totalAfterRefundDiscount = discount_value($totalAfterProductDiscount, $discountValue, $this->refundData['discount_type'] ?? 'number');
        $this->total_after_refund_discount = $totalAfterRefundDiscount;

        // Tính phí trả hàng
        $fee = 0;
        $refundFee = (float)($this->refundData['refund_fee'] ?? 0);
        if ($refundFee > 0) {
            if ($this->refundData['refund_fee_type'] === 'percent') {
                $fee = ($totalAfterRefundDiscount * $refundFee) / 100;
            } else {
                $fee = $refundFee;
            }
        }

        // Cập nhật tổng tiền trả cuối cùng (convert to cents)
        $finalTotal = $totalAfterRefundDiscount + $fee;
        $this->refundData['value'] = (int)($finalTotal * 100);
    }

    public function removeProduct($id): void
    {
        if ($this->isFinished) {
            session()->flash('error', 'Không thể xóa sản phẩm sau khi đã hoàn thành trả hàng');

            return;
        }

        unset($this->products[$id]);
        $this->updatedRefundData();
    }

    public function submitRefund()
    {
        $this->authorize('products.edit');

        \Log::info('RefundComponent::submitRefund() started', [
            'payment_id' => $this->payment_id,
            'refundData.product_payment_id' => $this->refundData['product_payment_id'] ?? 'NULL',
        ]);

        if (empty($this->refundData['product_payment_id'])) {
            \Log::warning('RefundComponent::submitRefund() - Missing product_payment_id');
            session()->flash('error', 'Vui lòng load đơn hàng trước khi refund');

            return;
        }

        if (empty($this->products)) {
            \Log::warning('RefundComponent::submitRefund() - Empty products');
            session()->flash('error', 'Vui lòng chọn ít nhất một sản phẩm để refund');

            return;
        }

        \Log::info('RefundComponent::submitRefund() BEFORE VALIDATE', ['refundData' => $this->refundData]);

        try {
            $this->validate();
            \Log::info('RefundComponent::submitRefund() AFTER VALIDATE - passed');

            DB::transaction(function () {
                // Tạo Refund từ array data
                $refund = Refund::create($this->refundData);

                foreach ($this->products as $item) {
                    RefundProduct::create([
                        'product_payment_id' => $refund->product_payment_id,
                        'product_refund_id' => $refund->id,
                        'product_id' => $item['product_id'],
                        'amount' => $item['amount'],
                        'price' => (float)$item['price'] * 100,
                        'discount_value' => (float)$item['discount_value'] * 100,
                        'discount_type' => $item['discount_type'],
                        'value' => (float)$item['value'] * 100,
                    ]);

                    // Update stock
                    product_logs(
                        $item['product_id'],
                        $refund->id,
                        Refund::class,
                        $item['amount'],
                        (float)$item['price'],
                        (float)$item['value'],
                        true
                    );
                }

                // Cập nhật refundData với ID mới
                $this->refundData['id'] = $refund->id;

                \Log::info('RefundComponent::submitRefund() transaction committed', ['refund_id' => $refund->id]);
            });

            \Log::info('RefundComponent::submitRefund() finished successfully');
            session()->flash('success', 'Hoàn trả hàng thành công');
            $this->dispatch('success', 'Hoàn trả hàng thành công');
            $this->isFinished = true;

        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = collect($e->errors())->flatten()->join(', ');
            \Log::warning('RefundComponent::submitRefund() VALIDATION FAILED: ' . $errors);
            session()->flash('error', 'Dữ liệu không hợp lệ: ' . $errors);
            $this->dispatch('error', 'Dữ liệu không hợp lệ: ' . $errors);

            throw $e;
        } catch (\Exception $e) {
            \Log::error('RefundComponent::submitRefund() FAILED: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);
            session()->flash('error', 'Có lỗi xảy ra: ' . $e->getMessage());
            $this->dispatch('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    public function closeTab()
    {
        $this->dispatch('remove-payment-tab', tabId: $this->tab_id);
    }

    public function render()
    {
        \Log::info('--- PRODUCT_MODULE_REFUND_RENDER ---', [
            'isFinished' => $this->isFinished,
            'products_count' => count($this->products),
            'product_payment_id' => $this->refundData['product_payment_id'] ?? 'NULL',
        ]);

        return view('modules/product::refund.view');
    }
}
