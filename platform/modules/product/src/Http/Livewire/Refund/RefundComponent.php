<?php

namespace Polirium\Modules\Product\Http\Livewire\Refund;

use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Rule;
use Livewire\Component;

use Polirium\Modules\Product\Http\Model\Payment\Payment;
use Polirium\Modules\Product\Http\Model\Refund\Refund;
use Polirium\Modules\Product\Http\Model\Refund\RefundProduct;
use Polirium\Modules\Product\Http\Support\ProductInventorySupport;

class RefundComponent extends Component
{
    #[Locked]
    public int $payment_id = 0;
    public string $tab_id = '';

    // Thay vì dùng Eloquent Model, dùng array để Livewire persist đúng cách
    public array $refundData = [];
    public float $total_after_product_discount = 0;
    public float $total_after_refund_discount = 0;
    public bool $isFinished = false;

    /** One key per return attempt: a resubmit finds the saved return instead of adding another. */
    #[Locked]
    public string $checkoutKey = '';

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
        $this->checkoutKey = (string) \Illuminate\Support\Str::uuid();
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
        $this->checkoutKey = (string) \Illuminate\Support\Str::uuid();
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

        if (! ProductInventorySupport::exportsStock($payment->status)) {
            $this->products = [];
            $this->updatedRefundData();
            session()->flash('error', "Hóa đơn {$payment->code} chưa xuất kho hoặc đã hủy, không thể trả hàng.");

            return;
        }

        // Load products từ payment, only what has not been returned yet
        $remaining = $this->returnableQuantities($payment->id);

        // Default return price is what the customer actually paid per unit: the
        // line after its own discount, then its share of an invoice discount.
        $lineTotal = fn ($item) => (float) ($item->total ?: discount_value((float) $item->value * (int) $item->amount, (float) $item->discount_value, $item->discount_type ?: 'number'));
        $linesSum = $payment->products->sum($lineTotal);
        $paidShare = $linesSum > 0 && (float) $payment->value > 0 && (float) $payment->value < $linesSum
            ? (float) $payment->value / $linesSum
            : 1.0;

        $this->products = [];
        foreach ($payment->products as $item) {
            if (($remaining[(int) $item->product_id] ?? 0) <= 0) {
                continue;
            }

            $unitPaid = round($lineTotal($item) / max(1, (int) $item->amount) * $paidShare);

            $this->products[$item->product_id] = [
                'product_id' => $item->product_id,
                'sold_amount' => $remaining[(int) $item->product_id],
                'amount' => 1,
                'price' => $unitPaid,
                'discount_value' => 0,
                'discount_type' => 'number',
                'value' => $unitPaid,
                'product' => $item->product?->only(['id', 'name', 'code', 'unit', 'price', 'cost']) ?? [],
            ];
        }

        $this->updatedRefundData();
    }

    public function updatedProducts($value, $key): void
    {
        if ($this->isFinished) {
            return;
        }

        // Only "{productId}.{column}" edits come from the form.
        if (substr_count($key, '.') !== 1) {
            $this->updatedRefundData();

            return;
        }

        [$id, $col] = explode('.', $key);
        $id = (int)$id;

        if (in_array($col, ['price', 'discount_value', 'value'], true)) {
            $this->products[$id][$col] = $this->toNumber($this->products[$id][$col] ?? 0);
        }

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
        foreach (['discount_value', 'refund_fee'] as $field) {
            $this->refundData[$field] = $this->toNumber($this->refundData[$field] ?? 0);
        }

        // Tổng sau giảm SP
        $totalAfterProductDiscount = array_sum(array_column($this->products, 'value'));
        $this->total_after_product_discount = $totalAfterProductDiscount;

        // Cập nhật số lượng và tổng sau giảm SP
        $this->refundData['amount'] = collect($this->products)->sum('amount');
        $this->refundData['total_payable_amount'] = (int) round($totalAfterProductDiscount);

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

        // Cập nhật tổng tiền trả cuối cùng (VNĐ)
        $finalTotal = $totalAfterRefundDiscount + $fee;
        $this->refundData['value'] = (int) round($finalTotal);
    }

    /** Currency inputs send the masked text ("20,000"); read it as a number. */
    protected function toNumber(mixed $value): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        return (float) preg_replace('/[^0-9.\-]/', '', (string) $value);
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
        // Same permission as the return route (products.payment.refund).
        $this->authorize('sales.payment.refund');

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

        if ($this->isFinished) {
            return;
        }

        try {
            $this->validate();

            $alreadySaved = false;

            try {
                DB::transaction(function () use (&$alreadySaved) {
                    // One return per key: a retry after a lost response finds the first one.
                    if ($existing = Refund::where('checkout_key', $this->checkoutKey)->first()) {
                        $this->refundData['id'] = $existing->id;
                        $this->refundData['code'] = $existing->code;
                        $alreadySaved = true;

                        return;
                    }

                    // Lock the sale so two returns against it are checked one after the other.
                    $payment = Payment::whereKey($this->payment_id)->lockForUpdate()->firstOrFail();

                    if (! user_can_access_branch($payment->branch_id)) {
                        throw new \RuntimeException('Bạn không có quyền với hóa đơn của chi nhánh này.');
                    }

                    // Drafts and cancelled invoices never took (or already gave back) their goods.
                    if (! ProductInventorySupport::exportsStock($payment->status)) {
                        throw new \RuntimeException("Hóa đơn {$payment->code} chưa xuất kho hoặc đã hủy, không thể trả hàng.");
                    }
                    $remaining = $this->returnableQuantities($payment->id);

                    foreach ($this->products as $item) {
                        $left = $remaining[(int) $item['product_id']] ?? 0;
                        if ((int) $item['amount'] > $left) {
                            $name = $item['product']['name'] ?? ('#' . $item['product_id']);

                            throw new \RuntimeException("Hàng {$name} chỉ còn trả được {$left} (đã trả hết hoặc đã trả một phần trước đó).");
                        }
                    }

                    // Ids come from the locked sale and the session, never from the browser.
                    $refund = Refund::create([
                        'checkout_key' => $this->checkoutKey,
                        'money_unit' => Refund::MONEY_UNIT_VND,
                        'product_payment_id' => $payment->id,
                        'user_id' => auth()->id(),
                        'user_created_id' => auth()->id(),
                    ] + $this->refundData);
                    $quantities = [];

                    foreach ($this->products as $item) {
                        RefundProduct::create([
                            'product_payment_id' => $refund->product_payment_id,
                            'product_refund_id' => $refund->id,
                            'product_id' => $item['product_id'],
                            'amount' => $item['amount'],
                            'price' => (int) round((float) $item['price']),
                            'discount_value' => (int) round((float) $item['discount_value']),
                            'discount_type' => $item['discount_type'],
                            'value' => (int) round((float) $item['value']),
                        ]);

                        $quantities[(int) $item['product_id']] = ($quantities[(int) $item['product_id']] ?? 0) + (int) $item['amount'];
                    }

                    // Goods go back to the branch that sold them, not the one selected now.
                    ProductInventorySupport::returnRefundItems($quantities, $refund, (int) ($payment->branch_id ?: user_branch() ?: 1));

                    $this->refundData['id'] = $refund->id;
                    $this->refundData['code'] = $refund->code;
                });
            } catch (\Illuminate\Database\QueryException $e) {
                // A concurrent submit with the same key won the insert.
                $existing = Refund::where('checkout_key', $this->checkoutKey)->first();
                if (! $existing) {
                    throw $e;
                }
                $this->refundData['id'] = $existing->id;
                $this->refundData['code'] = $existing->code;
                $alreadySaved = true;
            }

            $this->dispatch('success', $alreadySaved ? 'Phiếu trả hàng này đã được lưu trước đó.' : 'Hoàn trả hàng thành công');
            $this->isFinished = true;

        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = collect($e->errors())->flatten()->join(', ');
            \Log::warning('RefundComponent::submitRefund() VALIDATION FAILED: ' . $errors);
            session()->flash('error', 'Dữ liệu không hợp lệ: ' . $errors);
            $this->dispatch('error', 'Dữ liệu không hợp lệ: ' . $errors);

            throw $e;
        } catch (\Exception $e) {
            \Log::error('RefundComponent::submitRefund() FAILED: ' . $e->getMessage(), ['exception' => $e]);
            $this->dispatch('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Quantity of each product still returnable on a sale: sold minus every
     * return already recorded against it.
     *
     * @return array<int, int>
     */
    protected function returnableQuantities(int $paymentId): array
    {
        $sold = DB::table('product_payment_products')
            ->where('product_payment_id', $paymentId)
            ->groupBy('product_id')
            ->selectRaw('product_id, SUM(amount) as qty')
            ->pluck('qty', 'product_id');

        $returned = RefundProduct::query()
            ->where('product_payment_id', $paymentId)
            ->groupBy('product_id')
            ->selectRaw('product_id, SUM(amount) as qty')
            ->pluck('qty', 'product_id');

        // Returns completed from the accounting screen count too.
        $returnedElsewhere = DB::table('product_payment_refund_products as line')
            ->join('product_payment_refunds as refund', 'refund.id', '=', 'line.product_payment_refund_id')
            ->where('refund.product_payment_id', $paymentId)
            ->where('refund.status', 'success')
            ->whereNull('refund.deleted_at')
            ->whereNull('line.deleted_at')
            ->groupBy('line.product_id')
            ->selectRaw('line.product_id, SUM(line.amount) as qty')
            ->pluck('qty', 'product_id');

        $remaining = [];
        foreach ($sold as $productId => $qty) {
            $remaining[(int) $productId] = max(0, (int) $qty - (int) ($returned[$productId] ?? 0) - (int) ($returnedElsewhere[$productId] ?? 0));
        }

        return $remaining;
    }

    public function closeTab()
    {
        $this->dispatch('remove-payment-tab', tabId: $this->tab_id);
    }

    public function render()
    {
        return view('modules/product::refund.view');
    }
}
