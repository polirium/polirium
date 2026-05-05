<?php

namespace Polirium\Modules\Vendor\Http\Livewire\Purchase;

use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Polirium\Modules\Product\Http\Model\Product;
use Polirium\Modules\Vendor\Http\Model\Purchase\Purchase;
use Polirium\Modules\Vendor\Http\Model\Purchase\PurchaseProduct;
use Polirium\Modules\Vendor\Http\Model\Refund\Refund;
use Polirium\Modules\Vendor\Http\Model\Refund\RefundProduct;
use Polirium\Modules\Vendor\Http\Model\Vendor;

class RefundComponent extends Component
{
    public ?int $order_id = null;

    public ?int $refund_id = null;

    public ?int $vendor_id = null;

    public Refund $refund;

    public ?Purchase $purchase = null;

    #[Rule([
        'products' => ['required', 'array'],
        'products.*.product_id' => ['required', 'numeric', 'integer'],
        'products.*.amount' => ['required', 'numeric', 'integer'],
        'products.*.price' => ['required', 'numeric'],
        'products.*.value' => ['required', 'numeric'],
        'products.*.note' => ['nullable', 'string', 'max:191'],
        'products.*.purchase_id' => ['nullable', 'numeric', 'integer'],
    ])]
    public array $products = [];

    public array $lists = [
        'products' => [],
    ];

    public string $search = "";

    protected function rules() : array
    {
        return [
            'refund.code' => ['required', 'string', 'max:191', "unique:vendor_purchase_refunds,code,{$this->refund_id},id"],
            'refund.branch_id' => ['required', 'numeric', 'integer'],
            'refund.vendor_id' => ['nullable', 'numeric', 'integer'],
            'refund.purchase_id' => ['nullable', 'numeric', 'integer'],
            'refund.discount_value' => ['nullable', 'numeric'],
            'refund.discount_type' => ['nullable', 'string', 'in:percent,number'],
            'refund.user_created_id' => ['required', 'numeric', 'integer'],
            'refund.status' => ['required', 'string', 'in:temp,success,cancel'],
            'refund.total' => ['nullable', 'numeric'],
            'refund.value' => ['nullable', 'numeric'],
            'refund.note' => ['nullable', 'string', 'max:191'],
        ];
    }

    public function mount() : void
    {
        $this->order_id = request('purchase_id');
        $this->resetInput();
        $this->vendor_id = $this->refund->vendor_id;
    }

    #[Computed]
    public function vendor() : ?Vendor
    {
        return Vendor::find($this->refund->vendor_id);
    }

    #[Computed]
    public function vendors() : array
    {
        return Vendor::select('id', 'name')->pluck('name', 'id')->all();
    }

    #[On('vendor-saved')]
    public function onVendorSaved($id): void
    {
        $id = (int) $id;
        $this->vendor_id = $id;
        $this->refund->vendor_id = $id;

        // Clear computed cache to force re-query on next render
        unset($this->vendors);
        unset($this->vendor);

        // Prepare options for the JS update (matching POS style)
        $formattedOptions = [];
        foreach ($this->vendors as $vid => $vname) {
            $formattedOptions[] = ['id' => (string)$vid, 'label' => (string)$vname];
        }

        $this->dispatch('update-payment-options',
            id: 'vendor_id_select',
            options: $formattedOptions,
            value: (string)$id
        );

        \Log::info('RefundComponent onVendorSaved triggered', [
            'id' => $id,
            'vendor_exists' => (bool) Vendor::find($id),
            'options_count' => count($formattedOptions)
        ]);
    }

    public function updatedSearch($value)
    {
        if ( ! $value) {
            $this->lists['products'] = [];
            return;
        }

        $branchId = user_branch();
        $search = '%' . $value . '%';

        $this->lists['products'] = Product::with([
            'branches' => fn ($q) => $q->where('branch_id', $branchId)->select('branch_id')
        ])
            ->where(fn ($q) => $q->where('name', 'like', $search)->orWhere('code', 'like', $search))
            ->select(['id', 'name', 'code', 'unit', 'cost', 'price'])
            ->limit(15)
            ->get()
            ->toArray();
    }

    public function updatedProducts(mixed $value, string $key) : void
    {
        $split = explode(".", $key);
        $id = $split[0];
        $col = $split[1];

        if ($col === 'amount' && (int)$value <= 0) {
            $this->products[$id]['amount'] = 1;
        }

        $price = (float)$this->products[$id]['price'] * (float)$this->products[$id]['amount'];

        $this->products[$id]['value'] = $price;

        $this->updatedRefund();
    }

    public function updatedRefund(mixed $value = null, string $key = null) : void
    {
        $total = array_sum(array_column($this->products, 'value'));

        $discount = discount_value($total, (float)($this->refund->discount_value ?? 0), $this->refund->discount_type ?? 'vnd');

        $this->refund->total = $discount;

        if ($this->order_id) {
            $this->purchase->need_pay = $total;
        }
    }

    public function updatedVendorId($value): void
    {
        $this->refund->vendor_id = $value ?: null;
    }

    public function render()
    {
        return view('modules/vendor::purchase.refund.view');
    }

    public function resetInput() : void
    {
        $this->reset('refund', 'purchase', 'products', 'search');

        if ($this->refund_id) {
            $this->refund = Refund::with(['products.product'])->findOrFail($this->refund_id);
            $this->order_id = $this->refund->purchase_id;
            $this->purchase = $this->order_id
                ? Purchase::with('products.product')->find($this->order_id)
                : null;

            $this->products = [];
            foreach ($this->refund->products as $item) {
                $data = $item->toArray();
                $product = $item->product;
                $data['product'] = $product;
                $data['product']['amount'] = $product->amount;
                $this->products[$item->product_id] = $data;
            }

            $this->vendor_id = $this->refund->vendor_id;
        } else {

            $this->refund = new Refund;
            $this->refund->code = code_generate('THN', Refund::max('id'));
            $this->refund->branch_id = user_branch();
            $this->refund->discount_value = 0;
            $this->refund->discount_type = 'percent';
            $this->refund->user_created_id = auth()->id();
            $this->refund->value = 0;
            $this->refund->purchase_id = $this->order_id;

            if ($this->order_id) {
                $this->purchase = Purchase::with('products.product')->find($this->order_id);

                $this->refund->vendor_id = $this->purchase?->vendor_id;

                $this->products = [];
                foreach ($this->purchase->products as $item) {
                    $data = $item->toArray();
                    $product = $item->product;
                    $data['product'] = $product;
                    $data['product']['amount'] = $product->amount;
                    $this->products[$item->product_id] = $data;
                }

                $this->vendor_id = $this->refund->vendor_id;
            }
        }

        $this->updatedRefund();
    }

    public function selectProduct(int $id) : void
    {
        if (isset($this->products[$id]) && $this->products[$id]) {
            return;
        }

        $product = Product::findOrFail($id);

        $this->products[$id] = [
            'product_id' => $id,
            'amount' => 1,
            'price' => $product->cost,
            'value' => $product->cost,
            'note' => null,
            'product' => array_merge($product->toArray(), ['amount' => $product->amount])
        ];

        $this->updatedRefund();
    }

    public function removeProduct($id) : void
    {
        // Block removal on completed purchase returns for users without delete permission
        if (($this->refund->status ?? '') === 'success') {
            $user = auth()->user();
            if (! $user?->hasRole('admin') && ! $user?->can('vendors.refunds.delete')) {
                session()->flash('error', __('Không thể xóa sản phẩm trên phiếu đã hoàn thành.'));

                return;
            }
        }

        unset($this->products[$id]);
    }

    #[On('import-refund-products')]
    public function importProducts(array $products) : void
    {
        foreach ($products as $id => $productData) {
            if (!isset($this->products[$id])) {
                $this->products[$id] = $productData;
            }
        }
        $this->updatedRefund();
    }

    protected function validationAttributes() : array
    {
        return [
            'refund.code' => trans('modules/vendor::purchase.refund.code'),
            'refund.branch_id' => trans('Chi nhánh'),
            'refund.vendor_id' => trans('modules/vendor::vendor.name'),
            'refund.user_created_id' => trans('Người tạo'),
        ];
    }

    public function save(string $status)
    {
        $this->authorize($this->refund_id ? 'vendors.refunds.edit' : 'vendors.refunds.view');
        $this->refund->status = $status;
        $this->refund->vendor_id = $this->vendor_id ?: null;
        $this->refund->purchase_id = $this->order_id ?: $this->refund->purchase_id;

        if (empty($this->refund->code)) {
            $this->refund->code = code_generate('THN', Refund::max('id'));
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

        try {
            \Illuminate\Support\Facades\DB::transaction(function () {
                // --- Revert previous state if editing an existing successful refund ---
                if ($this->refund->exists) {
                    $originalRefund = Refund::with(['products.product', 'purchase'])->find($this->refund->id);
                    if ($originalRefund && in_array($originalRefund->status, ['success', 'completed', 'paid'])) {
                        // 1. Silently revert stock (a refund DECREASES stock, so reverting it INCREASES stock)
                        foreach ($originalRefund->products as $item) {
                            change_product_amount($item->product_id, $item->amount, true, $originalRefund->branch_id);
                        }

                        // 2. Delete old product_logs for this refund (clean slate)
                        \Polirium\Modules\Product\Http\Model\ProductLog::where('productable_type', Refund::class)
                            ->where('productable_id', $originalRefund->id)
                            ->delete();

                        // 3. Revert Vendor Stats
                        if ($originalRefund->vendor_id && $originalRefund->purchase?->status === 'success') {
                            $vendor = Vendor::find($originalRefund->vendor_id);
                            if ($vendor) {
                                $vendor->increment('debt', ((float) $originalRefund->value));
                                $vendor->increment('total', $originalRefund->value);
                            }
                        }
                    }
                }
                // -----------------------------------------------------------------------

                $this->refund->save();

                $this->refund->products()->delete();

                foreach ($this->products as $key => $value) {
                    $product = $value['product'];
                    unset($value['product']);
                    $value['vendor_purchase_refund_id'] = $this->refund->id;
                    $value['purchase_id'] = $this->order_id;
                    RefundProduct::create($value);

                    if ($this->refund->status === 'success') {
                        product_logs(
                            $value['product_id'],
                            $this->refund->id,
                            Refund::class,
                            $value['amount'],
                            $product['cost'],
                            $value['value'],
                            false,
                            $this->refund->branch_id
                        );
                    }
                }

                if ($this->refund->status === 'success' && $this->refund->vendor_id) {
                    if ($this->purchase?->status === 'success') {
                        $vendor = Vendor::find($this->refund->vendor_id);
                        if ($vendor) {
                            $vendor->decrement('debt', ((float) $this->refund->value));
                            $vendor->decrement('total', $this->refund->value);
                        }
                    }
                }

                if ($this->refund->status === 'success' && $this->purchase) {
                    $this->purchase->status = 'refund';
                    $this->purchase->save();
                }
            });
        } catch (\Exception $e) {
            \Log::error('RefundComponent save error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            session()->flash('error', 'Có lỗi xảy ra khi lưu phiếu trả: ' . $e->getMessage());

            return redirect(route('vendors.purchases.list-refunds'));
        }

        return redirect(route('vendors.purchases.list-refunds'));
    }
}
