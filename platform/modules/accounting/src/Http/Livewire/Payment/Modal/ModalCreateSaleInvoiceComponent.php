<?php

namespace Polirium\Modules\Accounting\Http\Livewire\Payment\Modal;

use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Polirium\Core\Base\Http\Models\User;
use Polirium\Modules\Customer\Http\Model\Customer;
use Polirium\Modules\Product\Http\Model\Payment\Payment;
use Polirium\Modules\Product\Http\Model\Payment\PaymentDelivery;
use Polirium\Modules\Product\Http\Model\Payment\PaymentMethod;
use Polirium\Modules\Product\Http\Model\Payment\PaymentPartnerDelivery;
use Polirium\Modules\Product\Http\Model\Payment\PaymentProduct;
use Polirium\Modules\Product\Http\Model\Payment\SaleChannel;
use Polirium\Modules\Product\Http\Model\Product;
use Polirium\Modules\Product\Http\Model\ProductLog;

class ModalCreateSaleInvoiceComponent extends Component
{
    public array $lists = [];

    public array $search = [];

    public array $products = [];

    public array $list_product = [];

    public $methods_payment = '';
    public $total_payment = 0;
    public $force_completed = false;

    #[Validate([
        'payment.code' => ['required', 'string', 'max:255'],
        'payment.branch_id' => ['required', 'integer', 'numeric', 'exists:branches,id'],
        'payment.customer_id' => ['nullable', 'integer', 'numeric'],
        'payment.discount_value' => ['nullable', 'numeric', 'min:0'],
        'payment.discount_type' => ['nullable', 'string', 'max:255', 'in:number,percent'],
        'payment.value' => ['required', 'numeric', 'min:0'],
        'payment.value_payment' => ['required', 'numeric', 'min:0'],
        'payment.total_cost' => ['required', 'integer', 'numeric', 'min:0'],
        'payment.type_payment' => ['required', 'array'],
        'payment.type_payment.*.method' => ['required', 'string'],
        'payment.type_payment.*.value' => ['required', 'numeric'],
        'payment.note' => ['nullable', 'string', 'max:255'],
        'payment.user_id' => ['required', 'numeric', 'integer', 'exists:users,id'],
        'payment.author_id' => ['required', 'numeric', 'integer', 'exists:users,id'],
        'payment.sale_channel_id' => ['nullable', 'numeric', 'integer'],
        'payment.status' => ['nullable', 'string', 'max:255', 'in:success,temp,draft,pending'],
    ])]
    public array $payment = [
        'code' => '',
        'branch_id' => null,
        'customer_id' => null,
        'discount_value' => 0,
        'discount_type' => 'number',
        'value' => 0,
        'value_payment' => 0,
        'total_cost' => 0,
        'type_payment' => [],
        'note' => null,
        'user_id' => null,
        'author_id' => null,
        'sale_channel_id' => null,
        'status' => 'success',
    ];

    public $payment_user_id;
    public $payment_sale_channel_id;
    public array $payment_delivery = [];

    protected function getListeners(): array
    {
        return [
            'show-modal-create-sale-invoice' => 'showModal',
            'sale-channel-created' => 'onSaleChannelCreated',
            'partner-delivery-created' => 'onPartnerDeliveryCreated',
            'customer-created' => 'onCustomerCreated',
            'payment-method-created' => 'onPaymentMethodCreated',
        ];
    }

    #[On('sale-channel-created')]
    public function onSaleChannelCreated($id)
    {
        $this->loadLists();
        $this->payment_sale_channel_id = $id;
    }

    #[On('partner-delivery-created')]
    public function onPartnerDeliveryCreated($id)
    {
        $this->loadLists();
        $this->payment_delivery['partner_delivery_id'] = $id;
    }

    #[On('customer-created')]
    public function onCustomerCreated($customerId)
    {
        $this->loadLists();
        $this->payment['customer_id'] = $customerId;
        $this->search['customer'] = '';
    }

    #[On('payment-method-created')]
    public function onPaymentMethodCreated($code)
    {
        $this->loadLists();
        $this->setPaymentMethod($code);
    }

    public function mount(): void
    {
        $this->loadLists();
    }

    #[On('show-modal-create-sale-invoice')]
    public function showModal($id = null): void
    {
        $this->authorize($id ? 'accountings.edit' : 'accountings.create');

        $this->resetInputs();
        $this->loadLists();

        if ($id) {
            $this->loadPayment($id);
        }

        $this->dispatch('modal', 'modal-create-sale-invoice');
    }

    public function loadPayment($id)
    {
        $payment = Payment::with(['products.product', 'latestDelivery', 'customer'])->find($id);
        if (! $payment) {
            return;
        }

        // Fill payment data
        $this->payment = $payment->toArray();
        // Fix JSON/Array fields
        $this->payment['type_payment'] = is_string($payment->type_payment) ? json_decode($payment->type_payment, true) : $payment->type_payment;
        if (empty($this->payment['type_payment'])) {
            $this->payment['type_payment'] = [];
        }

        // Fill Products
        foreach ($payment->products as $item) {
            $product = $item->product;
            if (! $product) {
                continue;
            }

            $this->list_product[] = $product->id;
            $this->products[$product->id] = [
                'product' => $product->toArray(),
                'product_id' => $product->id,
                'amount' => $item->amount,
                'value' => $item->value,
                'discount_value' => $item->discount_value,
                'discount_type' => $item->discount_type,
                'total' => $item->total,
                'note' => $item->note,
            ];
        }

        // Fill Delivery
        if ($payment->latestDelivery) {
            $this->payment_delivery = $payment->latestDelivery->toArray();
        }

        // Search inputs
        if ($payment->customer) {
            $this->search['customer'] = $payment->customer->name; // Just for display if needed or clear it
        }

        $this->payment_user_id = $this->payment['user_id'];
        $this->payment_sale_channel_id = $this->payment['sale_channel_id'];
        $this->force_completed = ! empty($payment->completed_at);

        // Recalculate totals to be safe
        $this->updatedPayment();
    }

    public function loadLists(): void
    {
        $this->lists['users'] = User::query()
            ->select(['id', 'name'])
            ->pluck('name', 'id')
            ->all();

        if (auth()->check() && ! isset($this->lists['users'][auth()->id()])) {
            $this->lists['users'][auth()->id()] = auth()->user()->name;
        }

        $this->lists['sale_channels'] = SaleChannel::where('is_active', true)->pluck('name', 'id')->all();
        $this->lists['payment_methods'] = PaymentMethod::where('is_active', true)->get();
        $this->lists['partner_deliveries'] = PaymentPartnerDelivery::where('is_active', true)->pluck('name', 'id')->all();
    }

    #[Computed]
    public function customers(): Collection
    {
        if (isset_value($this->search['customer'])) {
            return Customer::select(['id', 'name', 'code', 'phone', 'email'])
                ->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search['customer']}%")
                        ->orWhere('phone', 'like', "%{$this->search['customer']}%")
                        ->orWhere('email', 'like', "%{$this->search['customer']}%");
                })
                ->limit(10)
                ->get();
        }

        return new Collection();
    }

    #[Computed]
    public function customer(): Customer|Collection|null
    {
        $customerId = $this->payment['customer_id'] ?? null;
        if ($customerId) {
            return Customer::select(['id', 'name', 'phone'])->find($customerId);
        }

        return null;
    }

    #[Computed]
    public function productsSearched(): Collection
    {
        if (isset_value($this->search['product'])) {
            return Product::select(['id', 'name', 'code', 'price', 'unit'])
                ->where('name', 'like', "%{$this->search['product']}%")
                ->orWhere('code', 'like', "%{$this->search['product']}%")
                ->limit(10)
                ->get();
        }

        return new Collection();
    }

    public function selectCustomer(int $customerId): void
    {
        $this->payment['customer_id'] = $customerId;
        $this->search['customer'] = '';
    }

    public function removeCustomer(): void
    {
        $this->payment['customer_id'] = null;
        $this->search['customer'] = null;
    }

    public function addProduct(int $productId): void
    {
        if (in_array($productId, $this->list_product)) {
            $this->products[$productId]['amount']++;
            $this->recalculateProduct($productId);

            return;
        }

        $product = Product::find($productId);
        if (! $product) {
            return;
        }

        $this->list_product[] = $productId;
        $this->products[$productId] = [
            'product' => $product->toArray(),
            'product_id' => $product->id,
            'amount' => 1,
            'value' => $product->price,
            'discount_value' => 0,
            'discount_type' => 'number',
            'total' => $product->price,
            'note' => $product->note ?? '',
        ];

        $this->search['product'] = '';
        $this->updatedPayment();
    }

    public function removeProduct(int $productId): void
    {
        if (isset($this->products[$productId])) {
            unset($this->products[$productId]);
        }

        $key = array_search($productId, $this->list_product);
        if ($key !== false) {
            unset($this->list_product[$key]);
            $this->list_product = array_values($this->list_product);
        }

        $this->updatedPayment();
    }

    public function updatedProducts($value, $key): void
    {
        $split = explode('.', $key);
        $id = $split[0];
        $col = $split[1];

        if ($col === 'amount' && $value < 1) {
            $this->products[$id]['amount'] = 1;
            $this->recalculateProduct($id);

            return;
        }

        // Calculate the discounted unit price
        $discounted_unit_price = discount_value(
            (int)$this->products[$id]['value'],
            (int)$this->products[$id]['discount_value'],
            $this->products[$id]['discount_type']
        );
        $total_price = $discounted_unit_price * (int)$this->products[$id]['amount'];

        if ($col === 'discount_value' && $value < 0) {
            $this->products[$id]['discount_value'] = 0;
            $this->products[$id]['discount_type'] = 'number';
            $discounted_unit_price = (int)$this->products[$id]['value'];
            $total_price = $discounted_unit_price * (int)$this->products[$id]['amount'];
        }

        if ($col === 'total') {
            $base_total_price = (int)$this->products[$id]['value'] * (int)$this->products[$id]['amount'];

            if ((int)$this->products[$id]['total'] >= $base_total_price) {
                $this->products[$id]['discount_type'] = 'number';
                $this->products[$id]['discount_value'] = 0;
                $this->updatedPayment();

                return;
            }

            // Reverse calculate the discount based on the new total
            $total_discount_amount = $base_total_price - (int)$this->products[$id]['total'];
            $unit_discount = round($total_discount_amount / (int)$this->products[$id]['amount']);

            $this->products[$id]['discount_type'] = 'number';
            $this->products[$id]['discount_value'] = $unit_discount;
            $this->updatedPayment();

            return;
        }

        $this->products[$id]['total'] = $total_price;

        $this->updatedPayment();
    }

    protected function recalculateProduct(int $productId): void
    {
        if (! isset($this->products[$productId])) {
            return;
        }

        // Calculate the discounted unit price
        $discounted_unit_price = discount_value(
            (int)$this->products[$productId]['value'],
            (int)$this->products[$productId]['discount_value'],
            $this->products[$productId]['discount_type']
        );
        $total_price = $discounted_unit_price * (int)$this->products[$productId]['amount'];

        $this->products[$productId]['total'] = $total_price;

        $this->updatedPayment();
    }

    public function updatedPayment($value = null, $key = null): void
    {
        $this->payment['total_cost'] = collect($this->products)->sum('total');
        $this->payment['value'] = $this->payment['total_cost'];
        $this->total_payment = discount_value(
            (int)$this->payment['value'],
            (int)($this->payment['discount_value'] ?? 0),
            $this->payment['discount_type'] ?? 'number'
        );

        $typePayment = $this->payment['type_payment'] ?? [];
        $paymentCount = count($typePayment);

        if ($paymentCount <= 1) {
            // Single payment method: sync value_payment with total
            if ($key !== 'value_payment') {
                $this->payment['value_payment'] = $this->total_payment;
            }

            // Also sync type_payment[0] value
            if (! empty($typePayment) && isset($typePayment[0])) {
                $this->payment['type_payment'][0]['value'] = $this->payment['value_payment'];
            }
        } else {
            // Multi-payment: keep individual split amounts, sync value_payment from sum
            $this->payment['value_payment'] = collect($typePayment)->sum('value');
        }
    }

    public function setPaymentMethod(string $method): void
    {
        $methods = $this->lists['payment_methods'] ?? collect();
        $selectedMethod = $methods->where('code', $method)->first();
        $label = $selectedMethod ? $selectedMethod->name : $method;

        $valuePayment = $this->payment['value_payment'] ?? 0;
        if ($valuePayment <= 0 && $this->total_payment > 0) {
            $this->payment['value_payment'] = $this->total_payment;
            $valuePayment = $this->total_payment;
        }

        $this->payment['type_payment'] = [[
            'method' => $method,
            'value' => $valuePayment,
            'label' => $label,
        ]];
    }

    public function setMultiPayment(array $payments): void
    {
        $validPayments = [];
        foreach ($payments as $entry) {
            if (! empty($entry['method']) && ($entry['value'] ?? 0) > 0) {
                $validPayments[] = [
                    'method' => $entry['method'],
                    'value' => (int) $entry['value'],
                    'label' => $entry['label'] ?? $entry['method'],
                ];
            }
        }

        if (empty($validPayments)) {
            return;
        }

        $this->payment['type_payment'] = $validPayments;
        $this->payment['value_payment'] = collect($validPayments)->sum('value');
    }

    public function applyDiscount(string $type, $value): void
    {
        $this->payment['discount_type'] = $type;
        $this->payment['discount_value'] = (int)$value;
        $this->updatedPayment(null, 'discount_value');
    }

    protected function resetInputs(): void
    {
        $this->products = [];
        $this->list_product = [];
        $this->search = [];

        $defaultMethod = PaymentMethod::where('is_active', true)
            ->where('is_default', true)
            ->first() ?? PaymentMethod::where('is_active', true)->first();

        $this->payment = [
            'code' => code_generate('BH', Payment::max('id')),
            'branch_id' => user_branch(),
            'customer_id' => null,
            'discount_value' => 0,
            'discount_type' => 'number',
            'value' => 0,
            'value_payment' => 0,
            'total_cost' => 0,
            'type_payment' => $defaultMethod
                ? [['method' => $defaultMethod->code, 'value' => 0, 'label' => $defaultMethod->name]]
                : [],
            'note' => null,
            'user_id' => (int) auth()->id(),
            'author_id' => (int) auth()->id(),
            'sale_channel_id' => null,
            'status' => 'success',
        ];

        $defaultChannel = SaleChannel::where('is_active', true)->where('is_default', true)->first();
        $defaultDelivery = PaymentPartnerDelivery::where('is_active', true)->where('is_default', true)->first();

        $this->payment_user_id = $this->payment['user_id'];
        $this->payment_sale_channel_id = $defaultChannel?->id;
        $this->payment_delivery = [
            'partner_delivery_id' => $defaultDelivery?->id,
            'code' => null,
        ];
        $this->total_payment = 0;
        $this->force_completed = false;
    }

    public function closeModal(): void
    {
        $this->dispatch('modal', 'modal-create-sale-invoice', 'hide');
    }

    public function saveOnly(): void
    {
        $this->save();
    }

    public function saveDraft(): void
    {
        $this->payment['status'] = 'temp';
        $this->save();
    }

    public function save(): void
    {
        $this->authorize(! empty($this->payment['id']) ? 'accountings.edit' : 'accountings.create');

        if (empty($this->payment['code'])) {
            $this->payment['code'] = code_generate('BH', Payment::max('id'));
        }
        if (empty($this->payment['branch_id'])) {
            $this->payment['branch_id'] = user_branch() ?: 1;
        }
        if (empty($this->payment['author_id'])) {
            $this->payment['author_id'] = (int) auth()->id();
        }
        if (empty($this->payment['user_id'])) {
            $this->payment['user_id'] = (int) auth()->id();
        }
        if (empty($this->payment['status'])) {
            $this->payment['status'] = 'success';
        }

        $this->payment['user_id'] = $this->payment_user_id ?: (int) auth()->id();
        $this->payment['sale_channel_id'] = $this->payment_sale_channel_id;
        // Only recalculate totals if products are present.
        // When editing an existing invoice with empty cart (e.g. just updating delivery code),
        // preserve the original total_cost and value.
        if (count($this->products) > 0) {
            $this->payment['total_cost'] = collect($this->products)->sum('total');
            $this->payment['value'] = discount_value(
                (int)$this->payment['total_cost'],
                (int)($this->payment['discount_value'] ?? 0),
                $this->payment['discount_type'] ?? 'number'
            );
        }

        if (empty($this->payment['value_payment'])) {
            $this->payment['value_payment'] = $this->payment['value'];
        }

        $typePayment = $this->payment['type_payment'] ?? [];
        if (! is_array($typePayment) || empty($typePayment)) {
            $methods = $this->lists['payment_methods'] ?? collect();
            $defaultMethod = $methods->where('is_default', true)->first() ?? $methods->first();
            $methodCode = $defaultMethod ? $defaultMethod->code : null;
            $methodName = $defaultMethod ? $defaultMethod->name : null;

            $this->payment['type_payment'] = [[
                'method' => $methodCode,
                'value' => $this->payment['value_payment'],
                'label' => $methodName,
            ]];
        }

        // Cap value_payment to not exceed invoice value (handling change given to customer)
        $overpay = (float)($this->payment['value_payment'] ?? 0) - (float)($this->payment['value'] ?? 0);
        if ($overpay > 0 && is_array($this->payment['type_payment'])) {
            foreach ($this->payment['type_payment'] as &$tp) {
                if ($overpay <= 0) break;
                if (isset($tp['value']) && $tp['value'] > 0) {
                    $deduct = min($tp['value'], $overpay);
                    $tp['value'] -= $deduct;
                    $overpay -= $deduct;
                }
            }
            $this->payment['value_payment'] = (float)($this->payment['value'] ?? 0);
        }

        $this->validate();

        // Set completed_at nếu tất cả phương thức thanh toán đều là "thu tiền ngay"
        $collectedCodes = \Polirium\Modules\Product\Http\Model\Payment\PaymentMethod::query()
            ->where('target_payment_status', \Polirium\Modules\Product\Http\Model\Payment\PaymentMethod::STATUS_COMPLETED)
            ->pluck('code')
            ->toArray();
        $allCollected = true;
        $typePaymentArr = $this->payment['type_payment'] ?? [];
        foreach ($typePaymentArr as $tp) {
            if (! in_array($tp['method'] ?? '', $collectedCodes)) {
                $allCollected = false;

                break;
            }
        }
        if ($allCollected && count($typePaymentArr) > 0) {
            $this->payment['completed_at'] = now();
        }

        if ($this->force_completed && empty($this->payment['completed_at'])) {
            $this->payment['completed_at'] = now();
        } elseif (! $this->force_completed && ! $allCollected) {
            $this->payment['completed_at'] = null;
        }

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use (&$paymentModel, $allCollected) {
                if (! empty($this->payment['id'])) {
                    $paymentModel = Payment::find($this->payment['id']);
                    if ($paymentModel) {
                        $paymentModel->update($this->payment);

                        // Only clear and re-create products/logs if user has products in cart
                        if (count($this->products) > 0) {
                            // Clear old products
                            PaymentProduct::where('product_payment_id', $paymentModel->id)->delete();

                            // Reverse old stock changes and delete old logs to prevent duplicates
                            $oldLogs = ProductLog::where('productable_id', $paymentModel->id)
                                ->where('productable_type', Payment::class)
                                ->get();
                            foreach ($oldLogs as $oldLog) {
                                // Original log decreased stock (increase=false), so reverse by increasing back
                                change_product_amount($oldLog->product_id, $oldLog->amount, true);
                            }
                            ProductLog::where('productable_id', $paymentModel->id)
                                ->where('productable_type', Payment::class)
                                ->delete();
                        }
                    } else {
                        // Fallback if ID exists but not found (unlikely)
                        $paymentModel = Payment::create($this->payment);
                    }
                } else {
                    $paymentModel = Payment::create($this->payment);
                }

                foreach ($this->products as $key => $value) {
                    $product = $value['product'];
                    unset($value['product']);
                    $value['product_payment_id'] = $paymentModel->id;
                    PaymentProduct::create($value);

                    product_logs(
                        $value['product_id'],
                        $paymentModel->id,
                        Payment::class,
                        $value['amount'],
                        $product['price'],
                        $value['total'],
                        false
                    );
                }

                // Save Delivery
                if (! empty($this->payment_delivery['partner_delivery_id']) || ! empty($this->payment_delivery['code'])) {
                    PaymentDelivery::updateOrCreate(
                        ['product_payment_id' => $paymentModel->id],
                        [
                            'partner_delivery_id' => $this->payment_delivery['partner_delivery_id'],
                            'code' => $this->payment_delivery['code'] ?? null,
                            'status' => 'wait', // Default status
                            'value' => 0, // Fee can be added if needed
                        ]
                    );
                } else {
                    // Remove if empty
                    PaymentDelivery::where('product_payment_id', $paymentModel->id)->delete();
                }

                // Auto-create Cash Book Receipt (Phiếu Thu)
                if ($paymentModel->status === 'success') {
                    $exists = \Polirium\Modules\Accounting\Http\Model\Receipt::where('finance_type', \Polirium\Modules\Product\Http\Model\Payment\Payment::class)
                        ->where('finance_id', $paymentModel->id)
                        ->exists();

                    if (! $exists) {
                        $typePayment = $this->payment['type_payment'] ?? [];
                        foreach ($typePayment as $payment) {
                            $method = $payment['method'] ?? 'cash';
                            $label = $payment['label'] ?? 'Khác';
                            $value = $payment['value'] ?? 0;

                            if ($value > 0) {
                                $accountingTypeName = match ($method) {
                                    'cash' => 'Thu tiền mặt',
                                    'bank', 'transfer' => 'Thu chuyển khoản',
                                    default => 'Thu qua ' . $label,
                                };

                                if ($accountingTypeName) {
                                    $accountingType = \Polirium\Modules\Accounting\Http\Model\AccountingType::firstOrCreate(
                                        ['name' => $accountingTypeName],
                                        ['type' => 'receipt']
                                    );

                                    \Polirium\Modules\Accounting\Http\Model\Receipt::create([
                                        'code' => code_generate('PT', \Polirium\Modules\Accounting\Http\Model\Receipt::max('id') ?? 0),
                                        'branch_id' => $paymentModel->branch_id,
                                        'date' => now(),
                                        'type_id' => $accountingType->id,
                                        'value' => $value,
                                        'user_id' => $paymentModel->user_id,
                                        'user_created_id' => auth()->id(),
                                        'finance_type' => \Polirium\Modules\Product\Http\Model\Payment\Payment::class,
                                        'finance_id' => $paymentModel->id,
                                        'note' => 'Thu tiền bán hàng đơn ' . $paymentModel->code,
                                        'business_result' => 1, // Hạch toán kết quả kinh doanh
                                    ]);
                                }
                            }
                        }
                    }
                }
            });
        } catch (\Exception $e) {
            $this->dispatch('error', 'Có lỗi xảy ra khi lưu hóa đơn: ' . $e->getMessage());

            return;
        }

        $this->dispatch('refresh-datatable-product-payments');
        $this->dispatch('pg-refresh-detail-product-payment-table');
        $this->dispatch('success', trans('modules/accounting::accounting.invoice_saved'));
        $this->dispatch('modal', 'modal-create-sale-invoice', 'hide');
    }

    public function render()
    {
        return view('modules/accounting::payment.modal.modal-create-sale-invoice');
    }
}
