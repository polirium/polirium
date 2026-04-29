<?php

namespace Polirium\Modules\Product\Http\Livewire\Payment;

use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
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
use Polirium\Modules\Product\Http\Model\Payment\SaleChannel;
use Polirium\Modules\Product\Http\Model\Product;

class PaymentComponent extends Component
{
    public function getListeners()
    {
        return [
            'refresh-payment' => '$refresh',
            'refresh-payment' => '$refresh',
            'payment-product-selected' => 'selectedProduct',
            "payment-methods-value-{$this->tab_selected}" => 'paymentMethodsValue',
            "payment-methods-value-{$this->tab_selected}" => 'paymentMethodsValue',
            'customer-created' => 'selectCustomer',
            'open-draft-payment' => 'openDraft',
        ];
    }

    public function selectCustomer($customerId)
    {
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

    public function boot()
    {
        // RE-INIT SESSION KEY ON EVERY REQUEST
        // This is critical because protected properties like $session_key are NOT persisted by Livewire.
        // Without this, saveStateToSession() fails silently in subsequent requests because session_key is null.
        if ($this->tab_selected) {
            $this->session_key = 'payment_v2_tab_' . ($this->tab_selected ?? 'default');
        }
    }

    public function mount($tab_selected = null)
    {
        Assets::loadCss('professional-table');

        // Restore state from session if exists
        if (session()->has($this->session_key)) {
            $state = session()->get($this->session_key);
            $this->fill($state);

            // Fix Data Loss: Ensure list_product keys are integers
            if (! empty($this->list_product)) {
                $this->list_product = array_map('intval', $this->list_product);
            }

            // Re-sync extracted properties since fill() might not cover them if they match array keys in payment
            $this->payment_user_id = $this->payment['user_id'] ?? null;
            $this->payment_sale_channel_id = $this->payment['sale_channel_id'] ?? null;
            $this->total_payment = discount_value((int)($this->payment['value'] ?? 0), (int)($this->payment['discount_value'] ?? 0), $this->payment['discount_type'] ?? 'number');

            // FIX: Restore payment_methods from session
            // This property is saved to session but NOT restored by fill() because
            // it's only initialized conditionally in mount()'s else branch
            if (isset($state['payment_methods'])) {
                $this->payment_methods = $state['payment_methods'];
            }
        } else {
            \Log::info("No session found for {$this->session_key}. Resetting inputs.");
            // Initial setup if no session data
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

            $this->lists['type_delivery'] = [
                'normal' => 'Giao thường',
                'fast' => 'Giao nhanh',
                'day' => 'Giao trong ngày',
            ];

            $this->lists['status_delivery'] = [
                'wait' => 'Chờ xử lý',
                'delivery' => 'Đang giao hàng',
            ];

            $this->payment_methods = PaymentMethod::where('is_active', true)->get()->toArray();

            $this->resetInputs();
            \Log::info('After reset inputs. Product count: ' . count($this->products));

            // Sync initial stats to session
            $this->saveStateToSession();
        }
    }

    protected $session_key;

    protected function saveStateToSession()
    {
        if ($this->session_key) {
            \Log::info("SAVING SESSION: {$this->session_key} | Products: " . count($this->products));
            session()->put($this->session_key, [
                'payment' => $this->payment,
                'products' => $this->products,
                'list_product' => $this->list_product,
                'payment_delivery' => $this->payment_delivery,
                'search' => $this->search,
                'payment_delivery' => $this->payment_delivery,
                'search' => $this->search,
                'lists' => $this->lists, // Persist lists to avoid re-fetching on every mount if heavy
                'payment_methods' => $this->payment_methods,
            ]);
        }
    }

    public function dehydrate()
    {
        $this->saveStateToSession();
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

        $this->payment_methods = PaymentMethod::where('is_active', true)->get()->toArray();
    }

    public function removeCustomer()
    {
        $this->payment['customer_id'] = null;
        $this->search['customer'] = null;
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
        // Check if completing existing draft or creating new payment
        if (! empty($this->payment['id']) && ($this->payment['status'] ?? 'success') === 'draft') {
            // Complete existing draft instead of creating new one
            $this->completeDraft((int)$this->payment['id']);

            return;
        }

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

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use (&$paymentModel) {
                // Create Payment model and save
                $paymentModel = Payment::create($this->payment);

                // Lưu khi giao hàng
                // Lưu khi giao hàng (Automatically detect if partner is selected)
                if (! empty($this->payment_delivery['partner_delivery_id'])) {
                    $this->payment['is_delivery'] = 1; // Ensure payment record reflects delivery status
                    $paymentModel->update(['is_delivery' => 1]); // Update the already created model

                    $this->payment_delivery['product_payment_id'] = $paymentModel->id;
                    PaymentDelivery::create($this->payment_delivery);
                }

                foreach ($this->products as $key => $value) {
                    $product = $value['product'];
                    unset($value['product']);
                    $value['product_payment_id'] = $paymentModel->id;
                    PaymentProduct::create($value);

                    // Kiểm tra tồn kho trước khi xuất (trừ sản phẩm dịch vụ)
                    $productModel = Product::find($value['product_id']);
                    if ($productModel && $productModel->type !== 'service') {
                        $branchQty = $productModel->branches?->where('id', $paymentModel->branch_id)->first()?->pivot?->qty ?? 0;
                        if ($branchQty <= 0 || $branchQty < $value['amount']) {
                            throw new \Exception(__('modules/product::product.out_of_stock') . ': ' . ($productModel->name ?? ''));
                        }
                    }

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
            });
        } catch (\Exception $e) {
            $this->dispatch('error', 'Có lỗi xảy ra khi lưu hóa đơn: ' . $e->getMessage());

            return;
        }

        if ($paymentModel->id) {
            $this->dispatch('product-print-payment', url: route('products.print.print-payment', $paymentModel->id));

            // Dispatch event to front-end to close the tab if multiple exist
            $this->dispatch('close-or-reset-tab', tabId: $this->tab_selected);

            // Auto-create Cash Book Receipt (Phiếu Thu)
            if ($paymentModel->status === 'success') {
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

            // Clean the view instantly for the next order
            $this->newPayment();
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

        // Track if this is a new draft or update
        $isNewDraft = empty($this->payment['id']);
        $isUpdate = ! $isNewDraft;

        // Check if updating existing draft or creating new one
        if ($isUpdate) {
            // Update existing draft
            $paymentModel = Payment::find($this->payment['id']);

            if (! $paymentModel) {
                $this->dispatch('error', 'Không tìm thấy hóa đơn tạm.');

                return;
            }

            if ($paymentModel->status !== 'draft') {
                $this->dispatch('error', 'Hóa đơn này không phải là hóa đơn tạm.');

                return;
            }
        }

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use (&$paymentModel, $isUpdate) {
                if ($isUpdate) {
                    // Update payment data
                    $paymentModel->update($this->payment);
                } else {
                    // Create new Payment with status = 'draft'
                    $paymentModel = Payment::create($this->payment);

                    // Set payment ID for new draft
                    $this->payment['id'] = $paymentModel->id;
                }

                // Lưu khi giao hàng
                if (($this->payment['is_delivery'] ?? 0) == 1 && ! empty($this->payment_delivery['partner_delivery_id'])) {
                    $this->payment_delivery['product_payment_id'] = $paymentModel->id;

                    // Delete existing delivery if any, then create new one
                    PaymentDelivery::where('product_payment_id', $paymentModel->id)->delete();
                    PaymentDelivery::create($this->payment_delivery);
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
     * Hoàn thành hóa đơn tạm - trừ tồn kho, in hóa đơn
     */
    public function completeDraft(int $paymentId): void
    {
        $payment = Payment::find($paymentId);

        if (! $payment) {
            $this->dispatch('error', 'Không tìm thấy hóa đơn tạm.');

            return;
        }

        if ($payment->status !== 'draft') {
            $this->dispatch('error', 'Hóa đơn này không phải là hóa đơn tạm.');

            return;
        }

        // Trừ tồn kho cho các sản phẩm
        foreach ($payment->products as $paymentProduct) {
            $product = $paymentProduct->product;
            if ($product) {
                // Nếu không phải dịch vụ, kiểm tra tồn kho
                if ($product->type !== 'service') {
                    $branchQty = $product->branches?->where('id', $payment->branch_id)->first()?->pivot?->qty ?? 0;
                    if ($branchQty <= 0 || $branchQty < $paymentProduct->amount) {
                        $this->dispatch('error', __('modules/product::product.out_of_stock'));
                        return;
                    }
                }
                product_logs(
                    $paymentProduct->product_id,
                    $payment->id,
                    Payment::class,
                    $paymentProduct->amount,
                    $product->price ?? $paymentProduct->value,
                    $paymentProduct->total,
                    false
                );
            }
        }

        // Cập nhật status thành 'success' và đánh dấu ngày hoàn thành
        $payment->status = 'success';
        $payment->completed_at = now();
        $payment->save();

        // Cập nhật status trong component để view hiển thị đúng các nút action
        $this->payment['status'] = 'success';

        // Dispatch print event
        $this->dispatch('product-print-payment', url: route('products.print.print-payment', $payment->id));

        // Refresh draft list và datatable
        $this->dispatch('refresh-draft-list');
        $this->dispatch('refresh-datatable-product-payments');

        // KHÔNG reset form - giữ lại payment để hiển thị nút "In lại" và "Tạo hóa đơn mới"
        // User sẽ bấm nút "Tạo phiếu mới" để reset form

        $this->dispatch('success', 'Đã hoàn thành hóa đơn tạm.');
    }

    /**
     * Mở lại hóa đơn tạm để tiếp tục chỉnh sửa
     */
    #[On('open-draft-payment')]
    public function openDraft(int $paymentId, ?string $tabId = null): void
    {
        // Ignore if tabId is provided but doesn't match this component's tab
        if ($tabId && $tabId !== $this->tab_selected) {
            return;
        }

        $payment = Payment::with(['products', 'customer', 'saleChannel'])
            ->find($paymentId);

        if (! $payment) {
            $this->dispatch('error', 'Không tìm thấy hóa đơn tạm.');

            return;
        }

        if ($payment->status !== 'draft') {
            $this->dispatch('error', 'Hóa đơn này không phải là hóa đơn tạm.');

            return;
        }

        // Load payment data
        $this->payment = [
            'id' => $payment->id,
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
