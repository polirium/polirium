<?php

namespace Polirium\Modules\Product\Http\Livewire\Payment;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Polirium\Core\Base\Http\Models\User;
use Polirium\Core\UI\Facades\Assets;
use Polirium\Modules\Customer\Http\Model\Customer;
use Polirium\Modules\Product\Http\Model\Payment\Payment;
use Polirium\Modules\Product\Http\Model\Payment\PaymentDelivery;
use Polirium\Modules\Product\Http\Model\Payment\PaymentMethod;
use Polirium\Modules\Product\Http\Model\Payment\PaymentPartnerDelivery;
use Polirium\Modules\Product\Http\Model\Payment\PaymentProduct;
use Polirium\Modules\Product\Http\Model\Payment\PosCart;
use Polirium\Modules\Product\Http\Model\Payment\SaleChannel;
use Polirium\Modules\Product\Http\Model\Product;
use Polirium\Modules\Product\Http\Support\ProductInventorySupport;
use Polirium\Modules\Product\Support\VietQrService;

class PaymentComponent extends Component
{
    public function getListeners()
    {
        return [
            'refresh-payment' => '$refresh',
            "pos-add-product-{$this->tab_selected}" => 'addProduct',
            "payment-methods-value-{$this->tab_selected}" => 'paymentMethodsValue',
            'customer-created' => 'selectCustomer',
            'open-draft-payment' => 'openDraft',
        ];
    }

    public function selectCustomer($customerId, $tabId = null)
    {
        // A customer created from another tab (or outside the POS) is not for this invoice.
        if ($tabId !== $this->tab_selected) {
            return;
        }

        $this->payment['customer_id'] = $customerId;
        $this->search['customer'] = '';
    }

    public array $lists = [];

    public ?string $tab_selected = null;

    public array $search = [];

    public array $list_product = [];

    public int $total_payment = 0;

    public ?string $methods_payment = null;

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
        'payment.is_delivery' => ['required', 'boolean'],
        'payment.note' => ['nullable', 'string', 'max:255'],
        'payment.user_id' => ['required', 'numeric', 'integer', 'exists:users,id'],
        'payment.author_id' => ['required', 'numeric', 'integer', 'exists:users,id'],
        'payment.sale_channel_id' => ['nullable', 'numeric', 'integer'],
        'payment.status' => ['nullable', 'string', 'max:255', 'in:success,draft,pending'],
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
        'is_delivery' => 0,
        'note' => null,
        'user_id' => null,
        'author_id' => null,
        'sale_channel_id' => null,
        'status' => 'success',
    ];

    #[Validate([
        'products' => ['required', 'array'],
        'products.*.product_id' => ['required', 'integer', 'numeric'],
        'products.*.amount' => ['required', 'integer', 'numeric'],
        'products.*.value' => ['required', 'integer', 'numeric', 'min:0'],
        'products.*.discount_value' => ['nullable', 'numeric', 'min:0'],
        'products.*.discount_type' => ['nullable', 'string', 'max:255', 'in:number,percent'],
        'products.*.total' => ['required', 'integer', 'numeric'],
        'products.*.note' => ['nullable', 'string', 'max:255'],
    ])]
    public array $products = [];

    #[Validate([
        'payment_delivery.product_payment_id' => ['nullable', 'numeric', 'integer'],
        'payment_delivery.code' => ['nullable', 'string', 'max:255'],
        'payment_delivery.partner_delivery_id' => ['nullable', 'numeric', 'integer'],
        'payment_delivery.type' => ['nullable', 'string', 'max:255', 'in:normal,fast,day'],
        'payment_delivery.value' => ['nullable', 'numeric'],
        'payment_delivery.date' => ['nullable', 'date'],
        'payment_delivery.status' => ['nullable', 'string', 'in:wait,delivery'],
    ])]
    public array $payment_delivery = [
        'product_payment_id' => null,
        'code' => '',
        'partner_delivery_id' => null,
        'type' => 'normal',
        'value' => 0,
        'date' => null,
        'status' => 'wait',
    ];

    public $payment_methods;

    public bool $isPendingMethod = false;

    // Extracted properties to fix Entangle "property not found" errors
    public $payment_user_id;
    public $payment_sale_channel_id;

    /**
     * One key per sale attempt, kept across retries and reloads: saving the
     * same key twice returns the first invoice instead of a second one.
     */
    #[Locked]
    public string $checkoutKey = '';

    /**
     * The draft opened in this tab. Locked and re-read under a lock on save:
     * a crafted request cannot point the cart at someone else's invoice.
     */
    #[Locked]
    public ?int $draftId = null;

    public function mount($tab_selected = null)
    {
        Assets::loadCss('professional-table');

        $this->refreshList();

        $this->lists['users'] = User::query()->select(['id', 'name'])->pluck('name', 'id')->all();

        // Ensure current user is in the list
        if (auth()->check() && ! isset($this->lists['users'][auth()->id()])) {
            $this->lists['users'][auth()->id()] = auth()->user()->name;
        }

        $this->lists['type_delivery'] = [
            'normal' => 'Giao thường',
            'fast' => 'Giao nhanh',
            'day' => 'Giao trong ngày',
        ];

        $this->lists['status_delivery'] = [
            'wait' => 'Chờ xử lý',
            'delivery' => 'Đang giao hàng',
        ];

        // Restore the cart left open in this tab (reload, closed browser, new session).
        $state = $this->cart()?->state;

        if (is_array($state) && isset($state['payment'])) {
            $this->payment = $state['payment'];
            $this->products = $state['products'] ?? [];
            $this->list_product = array_map('intval', $state['list_product'] ?? array_keys($this->products));
            $this->payment_delivery = $state['payment_delivery'] ?? $this->payment_delivery;
            $this->checkoutKey = $state['checkout_key'] ?? (string) Str::uuid();
            // Carts saved before 1.1.1 kept the opened draft in payment.id.
            $draftId = $state['draft_id'] ?? ($state['payment']['id'] ?? null);
            $this->draftId = $draftId && Payment::whereKey($draftId)->where('status', 'draft')->exists() ? (int) $draftId : null;
            unset($this->payment['id']);

            $this->payment_user_id = $this->payment['user_id'] ?? null;
            $this->payment_sale_channel_id = $this->payment['sale_channel_id'] ?? null;
            $this->total_payment = discount_value((int)($this->payment['value'] ?? 0), (int)($this->payment['discount_value'] ?? 0), $this->payment['discount_type'] ?? 'number');

            $first = $this->payment['type_payment'][0] ?? null;
            $methodCode = is_array($first) ? ($first['method'] ?? null) : $first;
            if ($methodCode) {
                $method = collect($this->payment_methods)->firstWhere('code', $methodCode);
                $this->isPendingMethod = ($method['target_payment_status'] ?? PaymentMethod::STATUS_COMPLETED) !== PaymentMethod::STATUS_COMPLETED;
                $this->methods_payment = collect($this->payment['type_payment'])->pluck('label')->filter()->join(', ') ?: null;
            }
        } else {
            $this->resetInputs();
        }
    }

    /**
     * Saved after every request, so the cart is never only in the browser.
     */
    public function dehydrate()
    {
        $this->saveCart();
    }

    protected function cart(): ?PosCart
    {
        if (! $this->tab_selected) {
            return null;
        }

        return PosCart::ownedBy()->where('tab_key', $this->tab_selected)->first()
            ?? PosCart::create([
                'user_id' => (int) auth()->id(),
                'branch_id' => user_branch(),
                'tab_key' => $this->tab_selected,
                'type' => 'invoice',
                'position' => (int) PosCart::ownedBy()->max('position') + 1,
            ]);
    }

    protected function saveCart(): void
    {
        if (! $this->tab_selected) {
            return;
        }

        // Only update: a tab closed in the meantime must not come back.
        PosCart::ownedBy()->where('tab_key', $this->tab_selected)->update([
            'state' => json_encode([
                'payment' => $this->payment,
                'products' => $this->products,
                'list_product' => $this->list_product,
                'payment_delivery' => $this->payment_delivery,
                'checkout_key' => $this->checkoutKey,
                'draft_id' => $this->draftId,
            ]),
            'updated_at' => now(),
        ]);
    }

    /** Kept for the callers below: the cart is saved on dehydrate. */
    protected function saveStateToSession(): void
    {
    }

    public function updatedPaymentUserId($value)
    {
        $this->payment['user_id'] = $value;
        $this->saveStateToSession();
    }

    public function updatedPaymentSaleChannelId($value)
    {
        $this->payment['sale_channel_id'] = $value;
        $this->saveStateToSession();
    }

    /**
     * Search customers
     */
    #[Computed]
    public function customers(): Customer|Collection
    {
        if (isset_value($this->search['customer'])) {
            return Customer::select(['id', 'name', 'code', 'phone', 'email'])
            ->where(function ($q) {
                $q->where('name', 'like', "%{$this->search['customer']}%")
                ->orWhere('phone', 'like', "%{$this->search['customer']}%")
                ->orWhere('email', 'like', "%{$this->search['customer']}%")
                ;
            })
            ->limit(50)
            ->get();
        }

        return new Collection();
    }

    /**
     * Customer selected
     */
    #[Computed]
    public function customer(): Customer|Collection
    {
        $customerId = $this->payment['customer_id'] ?? null;
        if ($customerId) {
            return Customer::select(['id', 'name', 'phone', 'address'])
            ->find($customerId);
        }

        return new Collection();
    }

    #[Computed]
    public function productsSelected(): Product|Collection
    {
        if (count($this->list_product) > 0) {
            return Product::find($this->list_product, ['id', 'name', 'unit', 'price', 'note']);
        }

        return new Collection();
    }

    public function updatedProducts($value = null, $key = null): void
    {
        $split = explode('.', $key);
        $id = $split[0];
        $col = $split[1];

        if ($col === 'amount') {
            if ($value < 1) {
                $this->products[$id]['amount'] = 1;
                $this->updatedPayment(); // Will save session

                return;
            }
        }

        // Calculate the discounted unit price
        $discounted_unit_price = discount_value(
            (int)$this->products[$id]['value'],
            (int)$this->products[$id]['discount_value'],
            $this->products[$id]['discount_type']
        );
        $total_price = $discounted_unit_price * (int)$this->products[$id]['amount'];

        if ($col === 'discount_value') {
            if ($value < 0) {
                $this->products[$id]['discount_value'] = 0;
                $this->products[$id]['discount_type'] = 'number';
                $discounted_unit_price = (int)$this->products[$id]['value'];
                $total_price = $discounted_unit_price * (int)$this->products[$id]['amount'];
            }
        }

        if ($col === 'total') {
            $base_total_price = (int)$this->products[$id]['value'] * (int)$this->products[$id]['amount'];

            if ((int)$this->products[$id]['total'] >= $base_total_price) {
                $this->products[$id]['discount_type'] = 'number';
                $this->products[$id]['discount_value'] = 0;
                $this->updatedPayment(); // Will save session

                return;
            }

            // Reverse calculate the discount based on the new total
            // We find the total discount (cash), then divide by amount to get unit discount
            $total_discount_amount = $base_total_price - (int)$this->products[$id]['total'];
            $unit_discount = round($total_discount_amount / (int)$this->products[$id]['amount']);

            $this->products[$id]['discount_type'] = 'number';
            $this->products[$id]['discount_value'] = $unit_discount;
            $this->updatedPayment(); // Will save session

            return;
        }

        $this->products[$id]['total'] = $total_price;
        $this->updatedPayment();
    }

    public function updatedPayment($value = null, $key = null)
    {
        if ($key === 'branch_id') {
            user_branch($value);
        }

        if ($key === 'value_payment') {
            // Khi người dùng nhập "Khách thanh toán"
            // Không làm gì đặc biệt - chỉ lưu giá trị, không ghi đè giảm giá
            // (Đã có discount_value và discount_type được set qua applyDiscount)
        } else {
            // Khi các field khác thay đổi (product, discount...), cập nhật lại tổng tiền
            // total_cost = Tổng tiền hàng (trước giảm giá)
            $this->payment['total_cost'] = collect($this->products)->sum('total');
            $this->payment['value'] = $this->payment['total_cost'];
            $this->total_payment = discount_value((int)$this->payment['value'], (int)($this->payment['discount_value'] ?? 0), $this->payment['discount_type'] ?? 'number');

            // Nếu không phải là đang chỉnh sửa value_payment, thì auto-fill value_payment = total_payment
            if ($key !== 'value_payment') {
                $this->payment['value_payment'] = $this->total_payment;
                $this->dispatch('update-payment-value-' . $this->tab_selected, value: $this->total_payment);
            }

            // Cập nhật lại giá trị trong type_payment để đồng bộ
            // CHỈ áp dụng khi có 1 phương thức thanh toán duy nhất
            // Khi có nhiều phương thức (chia thanh toán), KHÔNG ghi đè vì giá trị đã đúng từ modal
            if (count($this->payment['type_payment']) === 1 && isset($this->payment['type_payment'][0])) {
                $this->payment['type_payment'][0]['value'] = $this->payment['value_payment'];
            }
        }

        $this->saveStateToSession();
    }

    public function updated($value)
    {
        $this->validateOnly($value);
        $this->saveStateToSession();
    }

    public function render()
    {
        // Format users list for TomSelect to recognize selection
        if (isset($this->lists['users'])) {
            $formattedUsers = [];
            $currentUserId = $this->payment['user_id'] ?? null;

            foreach ($this->lists['users'] as $key => $val) {
                // Handle mixed types (array from previous renders or string from mount)
                $id = is_array($val) ? ($val['id'] ?? $key) : $key;
                $name = is_array($val) ? ($val['name'] ?? $val['label'] ?? '') : $val;

                $formattedUsers[] = [
                    'id' => $id,
                    'name' => $name,
                    'selected' => ((int)$id === (int)$currentUserId),
                ];
            }
            $this->lists['users'] = $formattedUsers;
        }

        // Format sale_channels list for TomSelect to recognize selection
        if (isset($this->lists['sale_channels'])) {
            $formattedChannels = [];
            $currentChannelId = $this->payment['sale_channel_id'] ?? null;

            foreach ($this->lists['sale_channels'] as $key => $val) {
                // Determine ID and Name whether input is array or simple key-value
                $id = is_array($val) ? ($val['id'] ?? $key) : $key;
                $name = is_array($val) ? ($val['name'] ?? $val['label'] ?? '') : $val;

                $formattedChannels[] = [
                    'id' => $id,
                    'name' => $name,
                    'selected' => ((int)$id === (int)$currentChannelId),
                ];
            }
            $this->lists['sale_channels'] = $formattedChannels;
        }

        return view('modules/product::payment.payment');
    }

    public function resetInputs()
    {
        // Reset cart data first
        $this->products = [];
        $this->list_product = [];
        $this->checkoutKey = (string) Str::uuid();
        $this->draftId = null;
        $this->isPendingMethod = false;
        $this->methods_payment = null;

        $this->payment = [
            'code' => code_generate('BH', Payment::max('id')),
            'branch_id' => user_branch(),
            'customer_id' => null,
            'discount_value' => 0,
            'discount_type' => 'number',
            'value' => 0,
            'value_payment' => 0,
            'total_cost' => 0,
            'total_cost' => 0,
            'type_payment' => [],
            'is_delivery' => 0,
            'is_delivery' => 0,
            'note' => null,
            'user_id' => (int) auth()->id(),
            'author_id' => (int) auth()->id(),
            'sale_channel_id' => \Polirium\Modules\Product\Http\Model\Payment\SaleChannel::where('is_default', true)->first()?->id ?? null,
            'status' => 'success',
        ];

        $this->payment_delivery = [
            'product_payment_id' => null,
            'code' => null,
            'partner_delivery_id' => \Polirium\Modules\Product\Http\Model\Payment\PaymentPartnerDelivery::where('is_active', true)->where('is_default', true)->first()?->id ?? null,
            'type' => 'normal',
            'value' => 0,
            'date' => null,
            'status' => 'wait',
        ];

        // Sync extracted properties
        $this->payment_user_id = $this->payment['user_id'];
        $this->payment_sale_channel_id = $this->payment['sale_channel_id'];
    }

    public function setDeliveryMode(int $mode): void
    {
        $this->payment['is_delivery'] = $mode;
    }

    public function resetDelivery(): void
    {
        $this->payment_delivery = [
            'product_payment_id' => null,
            'code' => null,
            'partner_delivery_id' => \Polirium\Modules\Product\Http\Model\Payment\PaymentPartnerDelivery::where('is_active', true)->where('is_default', true)->first()?->id ?? null,
            'type' => 'normal',
            'value' => 0,
            'date' => null,
            'status' => 'wait',
        ];
    }

    #[On('sale-channel-created')]
    public function onSaleChannelCreated($id)
    {
        $this->refreshList();
        $this->payment['sale_channel_id'] = $id;
        $this->payment_sale_channel_id = $id;
    }

    #[On('partner-delivery-created')]
    public function onPartnerDeliveryCreated($id)
    {
        $this->refreshList();
        // Force array update detection and ensure ID type consistency
        $this->payment_delivery = array_merge($this->payment_delivery, [
            'partner_delivery_id' => $id,
        ]);
        $this->payment_delivery['partner_delivery_id'] = $id; // Double confirm
    }

    #[On('payment-refresh-list')]
    public function refreshList()
    {
        $this->lists['sale_channels'] = SaleChannel::where('is_active', true)->pluck('name', 'id')->all();
        $this->lists['partner_deliveries'] = PaymentPartnerDelivery::where('is_active', true)->pluck('name', 'id')->all();

        // Dispatch event to update AlpineJS component inside wire:ignore
        $formattedChannels = [];
        foreach ($this->lists['sale_channels'] as $id => $name) {
            $formattedChannels[] = ['id' => (string)$id, 'label' => (string)$name];
        }

        $this->dispatch(
            'update-payment-options',
            id: "payment_sale_channel_id_{$this->tab_selected}",
            options: $formattedChannels,
            value: $this->payment['sale_channel_id']
        );

        $this->payment_methods = $this->loadActivePaymentMethods();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function loadActivePaymentMethods(): array
    {
        return PaymentMethod::query()
            ->where('is_active', true)
            ->with('bankAccount')
            ->get()
            ->toArray();
    }

    #[Computed]
    public function vietQrUrl(): ?string
    {
        $typePayment = $this->payment['type_payment'] ?? [];
        if (! is_array($typePayment) || count($typePayment) !== 1) {
            return null;
        }

        $first = $typePayment[0] ?? null;
        $methodCode = is_array($first) ? ($first['method'] ?? null) : $first;
        if (! $methodCode) {
            return null;
        }

        $paymentMethod = collect($this->payment_methods)->firstWhere('code', $methodCode);
        $bankAccount = $paymentMethod['bank_account'] ?? null;
        if (! is_array($bankAccount) || empty($bankAccount['account_number']) || empty($bankAccount['bank_code'])) {
            return null;
        }

        $amount = (int) ($this->payment['value_payment'] ?? 0);
        if ($amount <= 0) {
            $amount = (int) ($this->total_payment ?? 0);
        }

        if ($amount <= 0) {
            return null;
        }

        $description = VietQrService::sanitizeDescription(
            (string) ($this->payment['code'] ?? $this->methods_payment ?? 'Thanh toan')
        );

        return VietQrService::imageUrl($bankAccount, $amount, $description);
    }

    public function removeCustomer()
    {
        $this->payment['customer_id'] = null;
        $this->search['customer'] = null;
    }

    /**
     * From the search box: a product id (picked from the list) or a typed or
     * scanned code. Adding a product already in the cart adds one more.
     */
    public function addProduct($productId = null, $code = null): void
    {
        $product = null;

        if ($productId) {
            $product = Product::with('branches:id')->find((int) $productId);
        } elseif (($code = trim((string) $code)) !== '') {
            $product = Product::with('branches:id')->where('code', $code)->first()
                ?? Product::with('branches:id')
                    ->where(function ($q) use ($code) {
                        $term = '%' . addcslashes($code, '%_\\') . '%';
                        $q->where('name', 'like', $term)->orWhere('code', 'like', $term);
                    })
                    ->orderBy('name')
                    ->orderBy('id')
                    ->first();
        }

        if (! $product) {
            $this->dispatch('error', trans('modules/product::product.no_products_found'));

            return;
        }

        $inCart = (int) ($this->products[$product->id]['amount'] ?? 0);

        if ($product->type !== 'service' && $product->amount < $inCart + 1) {
            $this->dispatch('error', $inCart > 0
                ? "Sản phẩm '{$product->name}' chỉ còn {$product->amount} trong kho."
                : "Sản phẩm '{$product->name}' đã hết hàng trong kho!");

            return;
        }

        if ($inCart > 0) {
            $this->products[$product->id]['amount'] = $inCart + 1;
            $this->updatedProducts($inCart + 1, $product->id . '.amount');

            return;
        }

        $this->list_product[] = $product->id;
        $this->products[$product->id] = [
            'product' => $product->only(['id', 'name', 'code', 'unit', 'price', 'note']),
            'product_id' => $product->id,
            'amount' => 1,
            'value' => $product->price,
            'discount_value' => 0,
            'discount_type' => 'number',
            'total' => $product->price,
            'note' => $product->note,
        ];

        $this->updatedPayment();
    }

    /**
     * Quantity from the cart's +/- buttons (sent once the clicks settle).
     */
    public function setQuantity(int $productId, $quantity): void
    {
        if (! isset($this->products[$productId])) {
            return;
        }

        $quantity = max(1, (int) $quantity);
        $this->products[$productId]['amount'] = $quantity;
        $this->updatedProducts($quantity, $productId . '.amount');
    }

    /**
     * Unit price and line discount from the price editor, in one request.
     */
    public function updateLinePrice(int $productId, $price, string $type, $discount): void
    {
        if (! isset($this->products[$productId])) {
            return;
        }

        $type = $type === 'percent' ? 'percent' : 'number';
        $price = max(0, (int) $price);
        $discount = max(0, (int) $discount);
        $discount = $type === 'percent' ? min(100, $discount) : min($price, $discount);

        $this->products[$productId]['value'] = $price;
        $this->products[$productId]['discount_type'] = $type;
        $this->products[$productId]['discount_value'] = $discount;
        $this->updatedProducts($price, $productId . '.value');
    }

    public function selectedProduct($product_id, $tab_id = null): void
    {
        // If tab_id is provided, ensure it matches this component's tab
        if ($tab_id && $tab_id !== $this->tab_selected) {
            return;
        }

        if (in_array($product_id, $this->list_product)) {
            return;
        }

        $this->list_product[] = $product_id;

        // Keep tracking existing items to not override their data (like amount, note, discount)
        $existingProductIds = array_keys($this->products);

        foreach ($this->products_selected as $key => $value) {
            // Only add default values if the product is NOT already in the cart
            // If it is already in the cart, its data is preserved in $this->products.
            if (! in_array($value->id, $existingProductIds)) {
                $this->products[$value->id] = [
                    'product' => $value->toArray(),
                    'product_id' => $value->id,
                    'amount' => 1,
                    'value' => $value->price,
                    'discount_value' => 0,
                    'discount_type' => 'number',
                    'total' => $value->price * 1,
                    'note' => $value->note,
                ];
            }
        }

        $this->updatedPayment();
    }

    /**
     * Remove a product from the cart
     */
    public function removeProduct(int $product_id): void
    {
        // Remove from products array
        if (isset($this->products[$product_id])) {
            unset($this->products[$product_id]);
        }

        // Remove from list_product array
        $key = array_search($product_id, $this->list_product);
        if ($key !== false) {
            unset($this->list_product[$key]);
            $this->list_product = array_values($this->list_product);
        }

        // Recalculate totals
        $this->updatedPayment();
    }

    public function paymentMethodsValue(array $array): void
    {
        // Lưu toàn bộ array với method và value
        $this->payment['type_payment'] = $array;
        $this->payment['value_payment'] = array_sum(array_column($array, 'value'));

        $this->methods_payment = count($array) > 0 ? collect($array)->pluck('label')->join(', ') : null;
        $this->updatedPayment();
    }

    /**
     * Set payment method with correct format for validation
     * This method is called by view buttons (Tiền mặt, Chuyển khoản, Thẻ)
     */
    public function setPaymentMethod(string $method): void
    {
        // Find the method in the loaded list
        $paymentMethod = collect($this->payment_methods)->firstWhere('code', $method);
        $label = $paymentMethod['name'] ?? $method;

        // Dùng target_payment_status từ DB thay vì hardcode
        $targetStatus = $paymentMethod['target_payment_status'] ?? PaymentMethod::STATUS_COMPLETED;
        $isPending = $targetStatus !== PaymentMethod::STATUS_COMPLETED;
        $this->isPendingMethod = $isPending;

        if ($isPending) {
            // Phương thức chưa thu tiền ngay (COD, Khác...): value_payment = 0
            $valuePayment = 0;
            $this->payment['value_payment'] = 0;
        } else {
            // Phương thức thu tiền ngay (Cash, Bank, Card): auto-fill
            $valuePayment = $this->payment['value_payment'] ?? 0;
            if ($valuePayment <= 0 && $this->total_payment > 0) {
                $this->payment['value_payment'] = $this->total_payment;
                $valuePayment = $this->total_payment;
            }
        }

        $this->payment['type_payment'] = [[
            'method' => $method,
            'value' => $valuePayment,
            'label' => $label,
        ]];

        $this->methods_payment = $label;
        $this->saveStateToSession();

        // Dispatch event to update UI for value_payment
        $this->dispatch('update-payment-value-' . $this->tab_selected, value: $valuePayment);
    }

    /**
     * Set discount type (used internally)
     */
    public function setDiscountType(string $type): void
    {
        $this->payment['discount_type'] = $type;
        $this->saveStateToSession();
    }

    /**
     * Apply discount with both type and value in a single call
     * This avoids race conditions when setting from Alpine.js
     */
    public function applyDiscount(string $type, $value): void
    {
        $this->payment['discount_type'] = $type;
        $this->payment['discount_value'] = (int) $value;

        // Recalculate total_payment with new discount
        $this->updatedPayment(null, 'discount_value');
    }

    public function save(): void
    {
        $this->authorize('sales.payment.index');

        // Only the locked draftId says which invoice is being completed.
        unset($this->payment['id']);

        // Ensure required fields are set
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
        if (! isset($this->payment['is_delivery'])) {
            $this->payment['is_delivery'] = 0;
        }
        // Determine status based on payment method's target_payment_status
        $typePayment = $this->payment['type_payment'] ?? [];
        $methodCode = 'cash'; // Default

        if (is_array($typePayment) && count($typePayment) > 0) {
            $firstItem = $typePayment[0];
            $methodCode = is_array($firstItem) ? ($firstItem['method'] ?? 'cash') : $firstItem;
        }

        $paymentMethod = collect($this->payment_methods)->firstWhere('code', $methodCode);
        $targetStatus = $paymentMethod['target_payment_status'] ?? PaymentMethod::STATUS_COMPLETED;

        // completed → status = 'success' (hoàn thành, đã thu tiền)
        // pending → status = 'pending' (chưa hoàn thành, chưa thu tiền)
        $this->payment['status'] = ($targetStatus === PaymentMethod::STATUS_COMPLETED) ? 'success' : 'pending';

        // total_cost = Tổng tiền hàng (trước giảm giá)
        $this->payment['total_cost'] = collect($this->products)->sum('total');

        // Sync extracted properties before validation/saving
        $this->payment['user_id'] = $this->payment_user_id ?: (int) auth()->id();
        $this->payment['sale_channel_id'] = $this->payment_sale_channel_id;

        // value = Số tiền khách cần trả (sau giảm giá)
        $this->payment['value'] = discount_value(
            (int)$this->payment['total_cost'],
            (int)($this->payment['discount_value'] ?? 0),
            $this->payment['discount_type'] ?? 'number'
        );

        // Ensure value_payment is set
        // For COD/other, keep as 0 if not manually changed (customer hasn't paid)
        // For other methods, auto-fill if empty
        $methodCode = 'cash';
        $typePayment = $this->payment['type_payment'] ?? [];
        if (is_array($typePayment) && count($typePayment) > 0) {
            $firstItem = $typePayment[0];
            $methodCode = is_array($firstItem) ? ($firstItem['method'] ?? 'cash') : $firstItem;
        }

        $isCodOrOther = in_array($methodCode, ['cod', 'other']);
        if (! $isCodOrOther && ($this->payment['value_payment'] ?? 0) <= 0) {
            $this->payment['value_payment'] = $this->payment['value'];
        }

        // Ensure type_payment has correct format for validation
        $typePayment = $this->payment['type_payment'] ?? [];
        if (! is_array($typePayment) || empty($typePayment)) {
            // Default to 'cash' or first active method
            $defaultMethod = collect($this->payment_methods)->firstWhere('is_default', true) ?? ($this->payment_methods[0] ?? ['code' => 'cash', 'name' => 'Tiền mặt']);

            $this->payment['type_payment'] = [[
                'method' => $defaultMethod['code'],
                'value' => $this->payment['value_payment'],
                'label' => $defaultMethod['name'],
            ]];
        } elseif (is_array($typePayment) && count($typePayment) > 0) {
            $firstItem = $typePayment[0] ?? null;
            // Nếu item đầu tiên là string, convert sang array với method + value
            // Nếu item đầu tiên là string, convert sang array với method + value
            if (is_string($firstItem)) {
                $method = $firstItem;
                // Look up in payment_methods
                $paymentMethod = collect($this->payment_methods)->firstWhere('code', $method);
                $label = $paymentMethod['name'] ?? $method;

                $this->payment['type_payment'] = [[
                    'method' => $method,
                    'value' => $this->payment['value_payment'],
                    'label' => $label,
                ]];
            }
        }

        // Cap value_payment to not exceed invoice value (handling change given to customer)
        $overpay = (float)($this->payment['value_payment'] ?? 0) - (float)($this->payment['value'] ?? 0);
        if ($overpay > 0 && is_array($this->payment['type_payment'])) {
            foreach ($this->payment['type_payment'] as &$tp) {
                if ($overpay <= 0) {
                    break;
                }
                if (isset($tp['value']) && $tp['value'] > 0) {
                    $deduct = min($tp['value'], $overpay);
                    $tp['value'] -= $deduct;
                    $overpay -= $deduct;
                }
            }
            $this->payment['value_payment'] = (float)($this->payment['value'] ?? 0);
        }

        $this->validate();

        if (! $this->ensureBranchAllowed()) {
            return;
        }

        // Set completed_at nếu tất cả phương thức thanh toán đều là "thu tiền ngay"
        $collectedCodes = PaymentMethod::query()
            ->where('target_payment_status', PaymentMethod::STATUS_COMPLETED)
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
        if ($allCollected && count($typePaymentArr) > 0 && $this->payment['status'] === 'success') {
            $this->payment['completed_at'] = now();
        }

        $alreadySaved = false;
        $paymentModel = null;

        try {
            DB::transaction(function () use (&$paymentModel, &$alreadySaved) {
                // The draft being completed, or one saved earlier with this key.
                $draftId = $this->draftId;
                $existing = $draftId
                    ? Payment::whereKey($draftId)->lockForUpdate()->first()
                    : Payment::where('checkout_key', $this->checkoutKey)->lockForUpdate()->first();

                if ($draftId && ! $existing) {
                    throw new \RuntimeException('Không tìm thấy hóa đơn tạm.');
                }

                if ($existing && ! user_can_access_branch($existing->branch_id)) {
                    throw new \RuntimeException('Bạn không có quyền với hóa đơn của chi nhánh này.');
                }

                if ($existing && $existing->status !== 'draft') {
                    // Already paid (retry after a lost response, a second click, another tab).
                    $paymentModel = $existing;
                    $alreadySaved = true;

                    return;
                }

                $data = collect($this->payment)->except(['id', 'code'])->all();

                if ($existing) {
                    // Completing a draft: it takes the cart as it is now.
                    $existing->update($data);
                    PaymentDelivery::where('product_payment_id', $existing->id)->delete();
                    PaymentProduct::where('product_payment_id', $existing->id)->delete();
                    $paymentModel = $existing;
                } else {
                    $paymentModel = Payment::create($this->payment + ['checkout_key' => $this->checkoutKey]);
                }

                // Lưu khi giao hàng (Automatically detect if partner is selected)
                if (! empty($this->payment_delivery['partner_delivery_id'])) {
                    $this->payment['is_delivery'] = 1;
                    $paymentModel->update(['is_delivery' => 1]);

                    $this->payment_delivery['product_payment_id'] = $paymentModel->id;
                    PaymentDelivery::create(collect($this->payment_delivery)->except('id')->all());
                }

                foreach ($this->products as $value) {
                    unset($value['product']);
                    $value['product_payment_id'] = $paymentModel->id;
                    PaymentProduct::create($value);
                }

                $this->postSale($paymentModel);
            });
        } catch (QueryException $e) {
            // A concurrent request with the same key inserted first.
            $paymentModel = Payment::where('checkout_key', $this->checkoutKey)->first();
            if (! $paymentModel || $paymentModel->status === 'draft') {
                $this->dispatch('error', 'Có lỗi xảy ra khi lưu hóa đơn: ' . $e->getMessage());

                return;
            }
            $alreadySaved = true;
        } catch (\Exception $e) {
            $this->dispatch('error', 'Có lỗi xảy ra khi lưu hóa đơn: ' . $e->getMessage());

            return;
        }

        if ($alreadySaved) {
            $this->dispatch('warning', "Hóa đơn {$paymentModel->code} đã được thanh toán trước đó, không tạo thêm.");
        }

        $this->dispatch('product-print-payment', url: route('products.print.print-payment', $paymentModel->id));
        $this->dispatch('refresh-draft-list');

        // Dispatch event to front-end to close the tab if multiple exist
        $this->dispatch('close-or-reset-tab', tabId: $this->tab_selected);

        // Clean the view instantly for the next order
        $this->newPayment();
    }

    /**
     * Stock out and the cash receipts for a sale whose lines are saved.
     * Runs inside the caller's transaction: all of it is saved, or none.
     */
    protected function postSale(Payment $payment): void
    {
        ProductInventorySupport::exportPaymentItems($payment->products()->with('product')->get(), $payment);

        if ($payment->status !== 'success' || ! class_exists(\Polirium\Modules\Accounting\Http\Model\Receipt::class)) {
            return;
        }

        foreach ((array) ($payment->type_payment ?? []) as $line) {
            $method = is_array($line) ? ($line['method'] ?? 'cash') : $line;
            $label = is_array($line) ? ($line['label'] ?? 'Khác') : 'Khác';
            $value = is_array($line) ? (float) ($line['value'] ?? 0) : (float) $payment->value_payment;

            if ($value <= 0) {
                continue;
            }

            $accountingType = \Polirium\Modules\Accounting\Http\Model\AccountingType::firstOrCreate(
                ['name' => match ($method) {
                    'cash' => 'Thu tiền mặt',
                    'bank', 'transfer' => 'Thu chuyển khoản',
                    default => 'Thu qua ' . $label,
                }],
                ['type' => 'receipt']
            );

            \Polirium\Modules\Accounting\Http\Model\Receipt::create([
                'code' => null, // numbered by the receipt counter
                'branch_id' => $payment->branch_id,
                'date' => now(),
                'type_id' => $accountingType->id,
                'value' => $value,
                'user_id' => $payment->user_id,
                'user_created_id' => auth()->id(),
                'finance_type' => Payment::class,
                'finance_id' => $payment->id,
                'note' => 'Thu tiền bán hàng đơn ' . $payment->code,
                'business_result' => 1, // Hạch toán kết quả kinh doanh
            ]);
        }
    }

    public function newPayment()
    {
        $this->resetInputs();

        $this->list_product = [];
        $this->products = [];
        $this->total_payment = 0;
    }

    public function printPayment()
    {

    }

    /**
     * Lưu tạm hóa đơn - KHÔNG trừ tồn kho, KHÔNG in hóa đơn
     */
    public function saveDraft(): void
    {
        $this->authorize('sales.payment.index');

        unset($this->payment['id']);

        // Ensure required fields are set
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
        if (! isset($this->payment['is_delivery'])) {
            $this->payment['is_delivery'] = 0;
        }

        // Set status = 'draft' cho hóa đơn tạm
        $this->payment['status'] = 'draft';

        // total_cost = Tổng tiền hàng (trước giảm giá)
        $this->payment['total_cost'] = collect($this->products)->sum('total');

        // Sync extracted properties before validation/saving
        $this->payment['user_id'] = $this->payment_user_id ?: (int) auth()->id();
        $this->payment['sale_channel_id'] = $this->payment_sale_channel_id;

        // value = Số tiền khách cần trả (sau giảm giá)
        $this->payment['value'] = discount_value(
            (int)$this->payment['total_cost'],
            (int)($this->payment['discount_value'] ?? 0),
            $this->payment['discount_type'] ?? 'number'
        );

        // Ensure value_payment is set
        // For COD/other, keep as 0 if not manually changed (customer hasn't paid)
        $methodCode = 'cash';
        $typePayment = $this->payment['type_payment'] ?? [];
        if (is_array($typePayment) && count($typePayment) > 0) {
            $firstItem = $typePayment[0];
            $methodCode = is_array($firstItem) ? ($firstItem['method'] ?? 'cash') : $firstItem;
        }

        $isCodOrOther = in_array($methodCode, ['cod', 'other']);
        if (! $isCodOrOther && ($this->payment['value_payment'] ?? 0) <= 0) {
            $this->payment['value_payment'] = $this->payment['value'];
        }

        // Ensure type_payment has correct format for validation
        $typePayment = $this->payment['type_payment'] ?? [];
        if (! is_array($typePayment) || empty($typePayment)) {
            $this->payment['type_payment'] = [[
                'method' => 'cash',
                'value' => $this->payment['value_payment'],
                'label' => __('modules/product::product.cash_payment_method'),
            ]];
        } elseif (is_array($typePayment) && count($typePayment) > 0) {
            $firstItem = $typePayment[0] ?? null;
            // Nếu item đầu tiên là string, convert sang array với method + value
            if (is_string($firstItem)) {
                $method = $firstItem;
                $labelMap = [
                    'cash' => __('modules/product::product.cash_payment_method'),
                    'bank' => __('modules/product::product.bank_transfer_payment_method'),
                    'card' => __('modules/product::product.card_payment_method'),
                    'cod' => __('modules/product::payment.cod'),
                    'other' => __('modules/product::payment.other'),
                ];
                $this->payment['type_payment'] = [[
                    'method' => $method,
                    'value' => $this->payment['value_payment'],
                    'label' => $labelMap[$method] ?? $method,
                ]];
            }
        }

        $this->validate();

        if (! $this->ensureBranchAllowed()) {
            return;
        }

        // Track if this is a new draft or update
        $isNewDraft = empty($this->draftId);

        try {
            DB::transaction(function () use (&$paymentModel, &$isNewDraft) {
                // The opened draft, or one already saved with this key (second click, retry).
                $paymentModel = $this->draftId
                    ? Payment::whereKey($this->draftId)->lockForUpdate()->first()
                    : Payment::where('checkout_key', $this->checkoutKey)->lockForUpdate()->first();

                if ($this->draftId && ! $paymentModel) {
                    throw new \RuntimeException('Không tìm thấy hóa đơn tạm.');
                }

                if ($paymentModel && ! user_can_access_branch($paymentModel->branch_id)) {
                    throw new \RuntimeException('Bạn không có quyền với hóa đơn của chi nhánh này.');
                }

                if ($paymentModel && $paymentModel->status !== 'draft') {
                    throw new \RuntimeException("Hóa đơn {$paymentModel->code} đã được thanh toán, không thể lưu tạm.");
                }

                if ($paymentModel) {
                    $isNewDraft = false;
                    $paymentModel->update(collect($this->payment)->except(['id', 'code'])->all());
                } else {
                    // Create new Payment with status = 'draft'
                    $paymentModel = Payment::create($this->payment + ['checkout_key' => $this->checkoutKey]);
                }

                // Lưu khi giao hàng
                if (($this->payment['is_delivery'] ?? 0) == 1 && ! empty($this->payment_delivery['partner_delivery_id'])) {
                    $this->payment_delivery['product_payment_id'] = $paymentModel->id;

                    // Delete existing delivery if any, then create new one
                    PaymentDelivery::where('product_payment_id', $paymentModel->id)->delete();
                    PaymentDelivery::create(collect($this->payment_delivery)->except('id')->all());
                }

                // Lưu products - KHÔNG GỌI product_logs() để KHÔNG trừ tồn kho
                // Delete existing products first
                PaymentProduct::where('product_payment_id', $paymentModel->id)->delete();

                foreach ($this->products as $key => $value) {
                    $product = $value['product'];
                    unset($value['product']);
                    $value['product_payment_id'] = $paymentModel->id;
                    PaymentProduct::create($value);
                }
            });
        } catch (\Exception $e) {
            $this->dispatch('error', 'Có lỗi xảy ra khi lưu hóa đơn tạm: ' . $e->getMessage());

            return;
        }

        // KHÔNG dispatch print event vì đây là draft

        // Notify và reset form (luôn reset để tạo "tab" mới)
        if ($isNewDraft) {
            $this->dispatch('success', __('modules/product::payment.draft_saved'));
        } else {
            $this->dispatch('success', __('modules/product::payment.draft_updated'));
        }

        $this->dispatch('refresh-draft-list');
        $this->dispatch('refresh-datatable-product-payments');

        // Luôn reset form sau khi lưu tạm để tạo tab mới
        $this->newPayment();
    }

    /**
     * The invoice's branch must be one the cashier works in (the field comes from the browser).
     */
    protected function ensureBranchAllowed(): bool
    {
        if (user_can_access_branch($this->payment['branch_id'] ?? null)) {
            return true;
        }

        $this->dispatch('error', 'Bạn không có quyền bán hàng ở chi nhánh này.');

        return false;
    }

    /**
     * Mở lại hóa đơn tạm để tiếp tục chỉnh sửa
     */
    #[On('open-draft-payment')]
    public function openDraft(int $paymentId, ?string $tabId = null): void
    {
        // Only the tab that asked opens it (every open tab hears the event).
        if ($tabId !== $this->tab_selected) {
            return;
        }

        $this->authorize('sales.payment.index');

        $payment = Payment::with(['products', 'customer', 'saleChannel'])
            ->find($paymentId);

        if (! $payment || ! user_can_access_branch($payment->branch_id)) {
            $this->dispatch('error', 'Không tìm thấy hóa đơn tạm.');

            return;
        }

        if ($payment->status !== 'draft') {
            $this->dispatch('error', 'Hóa đơn này không phải là hóa đơn tạm.');

            return;
        }

        // Load payment data
        $this->draftId = $payment->id;
        $this->payment = [
            'code' => $payment->code,
            'branch_id' => $payment->branch_id,
            'customer_id' => $payment->customer_id,
            'discount_value' => $payment->discount_value ?? 0,
            'discount_type' => $payment->discount_type ?? 'number',
            'value' => $payment->value ?? 0,
            'value_payment' => $payment->value_payment ?? 0,
            'total_cost' => $payment->total_cost ?? 0,
            'type_payment' => $payment->type_payment ?? [['method' => 'cash', 'value' => 0, 'label' => __('modules/product::product.cash_payment_method')]],
            'is_delivery' => $payment->is_delivery ?? 0,
            'note' => $payment->note,
            'user_id' => $payment->user_id,
            'author_id' => $payment->author_id,
            'sale_channel_id' => $payment->sale_channel_id,
            'status' => 'draft',
        ];

        // Load products
        $this->products = [];
        foreach ($payment->products as $product) {
            $this->products[$product->product_id] = [
                'product_id' => $product->product_id,
                'amount' => $product->amount,
                'value' => $product->value,
                'discount_value' => $product->discount_value ?? 0,
                'discount_type' => $product->discount_type ?? 'number',
                'total' => $product->total,
                'note' => $product->note,
                'product' => [
                    'id' => $product->product?->id,
                    'name' => $product->product?->name,
                    'code' => $product->product?->code,
                    'unit' => $product->product?->unit,
                    'price' => $product->product?->price,
                ],
            ];
        }

        // Load delivery data if exists
        $delivery = PaymentDelivery::where('product_payment_id', $payment->id)->first();
        if ($delivery) {
            $this->payment_delivery = [
                'id' => $delivery->id,
                'product_payment_id' => $delivery->product_payment_id,
                'code' => $delivery->code,
                'partner_delivery_id' => $delivery->partner_delivery_id,
                'type' => $delivery->type,
                'value' => $delivery->value,
                'date' => $delivery->date?->format('Y-m-d'),
                'status' => $delivery->status,
            ];
        } else {
            $this->resetDelivery();
        }

        $this->list_product = array_map('intval', array_keys($this->products));
        $this->checkoutKey = $payment->checkout_key ?: (string) Str::uuid();

        // Auto-switch to the branch of this draft
        user_branch($payment->branch_id);

        // Sync properties
        $this->payment_user_id = $this->payment['user_id'];
        $this->payment_sale_channel_id = $this->payment['sale_channel_id'];

        // Calculate total payment
        $this->total_payment = discount_value(
            (int)($this->payment['value'] ?? 0),
            (int)($this->payment['discount_value'] ?? 0),
            $this->payment['discount_type'] ?? 'number'
        );

        $this->dispatch('success', 'Đã mở hóa đơn tạm.');
    }
}
