<?php

namespace Polirium\Modules\Accounting\Http\Livewire\Payment;

use Livewire\Attributes\Computed;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Polirium\Modules\Accounting\Http\Model\Refund\Refund;
use Polirium\Modules\Accounting\Http\Model\Refund\RefundProduct;
use Polirium\Modules\Customer\Http\Model\Customer;
use Polirium\Modules\Product\Http\Model\Payment\Payment;
use Polirium\Modules\Product\Http\Model\Product;

class RefundComponent extends Component
{
    public ?int $payment_id = null;

    public ?int $refund_id = null;

    public Refund $refund;

    public Payment $payment;

    #[Rule([
        'products' => ['required', 'array'],
        'products.*.product_id' => ['required', 'numeric', 'integer'],
        'products.*.amount' => ['required', 'numeric', 'integer'],
        'products.*.price' => ['required', 'numeric'],
        'products.*.value' => ['required', 'numeric'],
        'products.*.note' => ['nullable', 'string', 'max:191'],
        'products.*.product_payment_id' => ['nullable', 'numeric', 'integer'],
    ])]
    public array $products = [];

    public array $lists = [
        'products' => [],
    ];

    public string $search = '';

    protected function rules(): array
    {
        return [
            'refund.code' => ['required', 'string', 'max:191', "unique:product_payment_refunds,code,{$this->refund_id},id"],
            'refund.branch_id' => ['required', 'numeric', 'integer'],
            'refund.customer_id' => ['required', 'numeric', 'integer'],
            'refund.product_payment_id' => ['nullable', 'numeric', 'integer'],
            'refund.discount_value' => ['nullable', 'numeric'],
            'refund.discount_type' => ['nullable', 'string', 'in:percent,number'],
            'refund.user_created_id' => ['required', 'numeric', 'integer'],
            'refund.status' => ['required', 'string', 'in:temp,success,cancel'],
            'refund.total' => ['nullable', 'numeric'],
            'refund.value' => ['nullable', 'numeric'],
            'refund.note' => ['nullable', 'string', 'max:191'],
        ];
    }

    public function mount(): void
    {
        $this->payment_id = request('id'); // Get ID from router parameter {id}
        $this->lists['customers'] = Customer::select('id', 'name')->pluck('name', 'id')->all();
        $this->resetInput();
    }

    #[Computed]
    public function customer(): Customer
    {
        return Customer::find($this->refund->customer_id) ?? new Customer(['name' => 'N/A']);
    }

    public function updatedSearch($value)
    {
        if ($value) {
            $this->lists['products'] = Product::where(function ($query) use ($value) {
                $query->where('name', 'like', '%' . $value . '%')
                ->orWhere('code', 'like', '%' . $value . '%');
            })
            ->limit(50)
            ->get()
            ->transform(function ($item) {
                $item->amount = 1; // Default fetch amount

                return $item;
            })
            ->toArray();
        } else {
            $this->lists['products'] = [];
        }
    }

    public function updatedProducts(mixed $value, string $key): void
    {
        $split = explode('.', $key);
        $id = $split[0];
        $col = $split[1];

        if ($col === 'amount' && (int)$value <= 0) {
            $this->products[$id]['amount'] = 1;
        }

        $price = (float)$this->products[$id]['price'] * (float)$this->products[$id]['amount'];

        $this->products[$id]['value'] = $price;

        $this->updatedRefund();
    }

    public function updatedRefund(mixed $value = null, string $key = null): void
    {
        $total = array_sum(array_column($this->products, 'value'));

        $discount = discount_value($total, (float)($this->refund->discount_value ?? 0), $this->refund->discount_type ?? 'vnd');

        $this->refund->total = $discount;
        $this->refund->value = $discount; // Final value to pay/refund
    }

    public function render()
    {
        return view('modules/accounting::payment.refund.view');
    }

    public function resetInput(): void
    {
        $this->reset('refund', 'payment', 'products', 'search');

        if ($this->refund_id) {
            $this->refund = Refund::with(['products.product'])->findOrFail($this->refund_id);

            $this->products = $this->refund->products
            ->transform(function ($item) {
                $item->product->amount = $item->product->amount;

                return $item;
            })
            ->toArray();
        } else {

            $this->refund = new Refund();
            $this->refund->code = code_generate('THX', Refund::max('id')); // THX = Tra Hang Xuat (Return Sale)
            $this->refund->branch_id = user_branch();
            $this->refund->discount_value = 0;
            $this->refund->discount_type = 'percent';
            $this->refund->user_created_id = auth()->id();
            $this->refund->value = 0;
            $this->refund->product_payment_id = $this->payment_id;

            if ($this->payment_id) {
                $this->payment = Payment::find($this->payment_id);

                $this->refund->customer_id = $this->payment?->customer_id;

                // Load initial products from the original payment
                $this->products = $this->payment->products->map(function ($item) {
                    return [
                        'product_id' => $item->product_id,
                        'amount' => $item->amount,
                        'price' => $item->price ?? ($item->value / ($item->amount ?: 1)), // Use stored price or calc
                        'value' => $item->value, // This is usually total
                        'note' => null,
                        'product_payment_id' => $this->payment_id,
                        'product' => $item->product, // Needed for display name/code
                    ];
                })->toArray();
            }
        }

        $this->updatedRefund();
    }

    public function selectProduct(int $id): void
    {
        if (isset($this->products[$id]) && $this->products[$id]) {
            return;
        }

        $product = Product::findOrFail($id);

        $this->products[$id] = [
            'product_id' => $id,
            'amount' => 1,
            'price' => $product->cost, // Should this be selling price? Default to cost or retail?
                                      // Suggestion: use selling price for sales refund
            'value' => $product->cost,
            'note' => null,
            'product' => $product,
        ];

        $this->updatedRefund();
    }

    public function removeProduct($id): void
    {
        unset($this->products[$id]);
        $this->updatedRefund();
    }

    protected function validationAttributes(): array
    {
        return [
            'refund.code' => trans('modules/accounting::payment.refund.code'),
            'refund.branch_id' => trans('modules/accounting::accounting.branch'),
            'refund.customer_id' => trans('modules/customer::customer.name'),
            'refund.user_created_id' => trans('modules/accounting::accounting.created_by'),
        ];
    }

    public function save(string $status)
    {
        $this->authorize('accountings.refunds');

        $this->refund->status = $status;

        if (empty($this->refund->code)) {
            $this->refund->code = code_generate('THX', Refund::max('id'));
        }

        if (empty($this->refund->branch_id)) {
            $this->refund->branch_id = user_branch();
        }

        if (empty($this->refund->user_created_id)) {
            $this->refund->user_created_id = auth()->id();
        }

        $this->validate(
            $this->rules(),
            [],
            $this->validationAttributes()
        );

        $this->refund->save();

        $this->refund->products()->delete(); // Simple way to handle updates: delete old lines and re-create.
        // Note: If ID persistence matters, this is destructive.

        foreach ($this->products as $key => $value) {
            $product = $value['product'];
            unset($value['product']); // Remove object before saving

            $value['product_payment_refund_id'] = $this->refund->id;
            $value['product_payment_id'] = $this->payment_id;

            RefundProduct::create($value);

            if ($this->refund->status === 'success') {
                // Return stock = Increase stock
                change_product_amount(
                    $value['product_id'],
                    $value['amount'],
                    true, // increase
                    $this->refund->branch_id
                );

                product_logs(
                    $value['product_id'],
                    $this->refund->id,
                    Refund::class,
                    $value['amount'],
                    $value['price'], // Log value
                    $value['value'],
                    true // true = increase? Need to verify change function usage.
                    // change_product_amount 3rd arg: $increase (bool).
                    // product_logs 7th arg: $increase (bool).
                );
            }
        }

        if ($this->refund->status === 'success') {
            // Optionally update original payment status or add note?
            // For now, let's just save.
            // Maybe update customer balance if credit/wallet system exists?
            // Currently no clear wallet system found, so just stock update.
        }

        $this->dispatch('success', 'Đã lưu phiếu trả hàng thành công.');

        return redirect(route('accountings.payment.index'));
    }
}
