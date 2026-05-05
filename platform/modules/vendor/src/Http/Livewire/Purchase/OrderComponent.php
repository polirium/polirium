<?php

namespace Polirium\Modules\Vendor\Http\Livewire\Purchase;

use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Polirium\Modules\Product\Http\Model\Product;
use Polirium\Modules\Vendor\Http\Model\Purchase\Purchase;
use Polirium\Modules\Vendor\Http\Model\Purchase\PurchaseProduct;
use Polirium\Modules\Vendor\Http\Model\Vendor;

class OrderComponent extends Component
{
    public int $order_id = 0;

    public Purchase $purchase;

    #[Rule([
        'products' => ['required', 'array'],
        'products.*.branch_id' => ['required', 'numeric', 'integer'],
        'products.*.product_id' => ['required', 'numeric', 'integer'],
        'products.*.vendor_purchase_id' => ['nullable', 'numeric', 'integer'],
        'products.*.amount' => ['required', 'numeric', 'integer'],
        'products.*.price' => ['required', 'numeric'],
        'products.*.discount_value' => ['nullable', 'numeric'],
        'products.*.discount_type' => ['nullable', 'string', 'in:percent,number'],
        'products.*.value' => ['required', 'numeric'],
        'products.*.note' => ['nullable', 'string', 'max:191'],
    ])]
    public array $products = [];

    public array $lists = [
        'products' => [],
    ];

    public array $state = [];

    public string $search = "";

    protected function rules() : array
    {
        return [

            'state.code' => ['required', 'string', 'max:191', "unique:vendor_purchases,code,{$this->order_id},id"],
            'state.branch_id' => ['required', 'numeric', 'integer'],
            'state.vendor_id' => ['nullable', 'numeric', 'integer'],
            'state.discount_value' => ['nullable', 'numeric'],
            'state.discount_type' => ['nullable', 'string', 'in:percent,number'],
            'state.user_created_id' => ['required', 'numeric', 'integer'],
            'state.status' => ['required', 'string', 'in:temp,success,cancel'],
            'state.total' => ['nullable', 'numeric'],
            'state.need_pay' => ['nullable', 'numeric'],
            'state.value' => ['nullable', 'numeric'],
            'state.note' => ['nullable', 'string', 'max:191'],
        ];

    }

    public function mount() : void
    {
        $this->lists['vendors'] = Vendor::select('id', 'name')->pluck('name', 'id')->all();
        $this->resetInput();
    }

    #[Computed]
    public function vendor() : Vendor
    {
        return Vendor::find($this->state['vendor_id'] ?? 0);
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

        $discount = discount_value($price, (float)$this->products[$id]['discount_value'], $this->products[$id]['discount_type'] ?? 'percent');

        $this->products[$id]['value'] = $discount;

        $this->calculateTotals();
    }

    public function updatedState($value, $key)
    {
        $this->calculateTotals();
    }

    public function calculateTotals() : void
    {
        $total = array_sum(array_column($this->products, 'value'));

        $discount = discount_value($total, (float)($this->state['discount_value'] ?? 0), $this->state['discount_type'] ?? 'percent');

        $this->state['total'] = $total;
        $this->state['need_pay'] = $discount;
    }

    public function render()
    {
        return view('modules/vendor::purchase.order.view');
    }

    public function resetInput() : void
    {
        $this->reset('purchase', 'products', 'search');

        if ($this->order_id) {
            $this->purchase = Purchase::with(['products.product'])->findOrFail($this->order_id);
            $this->state = $this->purchase->toArray();

            $this->products = [];
            foreach ($this->purchase->products as $item) {
                $data = $item->toArray();
                $data['product'] = $item->product;
                $data['product']['amount'] = $item->product->amount;
                $this->products[$item->product_id] = $data;
            }
        } elseif (request()->query('copy_id')) {
            $copyId = request()->query('copy_id');
            $purchaseToCopy = Purchase::with(['products.product'])->findOrFail($copyId);

            $this->purchase = new Purchase;
            $this->state = $purchaseToCopy->toArray();

            // Reset fields for new purchase
            $this->state['code'] = code_generate('NH', Purchase::max('id'));
            $this->state['status'] = 'temp';
            $this->state['user_created_id'] = auth()->id();
            $this->state['created_at'] = now();
            unset($this->state['id']);
            unset($this->state['uuid']);

            $this->products = [];
            foreach ($purchaseToCopy->products as $item) {
                $data = $item->toArray();
                $data['vendor_purchase_id'] = null;
                $data['product'] = $item->product;
                $data['product']['amount'] = $item->product->amount;
                $this->products[$item->product_id] = $data;
            }

        } else {
            $this->purchase = new Purchase;
            $this->state = [
                'code' => code_generate('NH', Purchase::max('id')),
                'branch_id' => user_branch(),
                'discount_value' => 0,
                'discount_type' => 'percent',
                'user_created_id' => auth()->id(),
                'total' => 0,
                'need_pay' => 0,
                'value' => 0,
                'note' => '',
                'vendor_id' => null,
                'status' => 'temp'
            ];
        }
    }

    public function selectProduct(int $id) : void
    {
        if (isset($this->products[$id]) && $this->products[$id]) {
            return;
        }

        $product = Product::findOrFail($id);

        $this->products[$id] = [
            'product_id' => $id,
            'branch_id' => user_branch(),
            'vendor_purchase_id' => null,
            'amount' => 1,
            'price' => $product->cost,
            'discount_value' => 0,
            'discount_type' => 'percent',
            'value' => $product->cost,
            'note' => null,
            'product' => $product
        ];

        $this->calculateTotals();
    }

    public function removeProduct($id) : void
    {
        // Block removal on completed purchase orders for users without delete permission
        if (($this->state['status'] ?? '') === 'success') {
            $user = auth()->user();
            if (! $user?->hasRole('admin') && ! $user?->can('vendors.purchases.delete')) {
                session()->flash('error', __('Không thể xóa sản phẩm trên phiếu đã hoàn thành.'));

                return;
            }
        }

        unset($this->products[$id]);
    }

    #[On('import-purchase-products')]
    public function importProducts(array $products) : void
    {
        foreach ($products as $id => $productData) {
            if (!isset($this->products[$id])) {
                $productData['branch_id'] = user_branch();
                $productData['vendor_purchase_id'] = null;
                $this->products[$id] = $productData;
            }
        }
        $this->calculateTotals();
    }

    protected function validationAttributes() : array
    {
        return [
            'state.code' => trans('modules/vendor::purchase.code'),
            'state.branch_id' => trans('Chi nhánh'),
            'state.vendor_id' => trans('modules/vendor::vendor.name'),
            'state.user_created_id' => trans('Người tạo'),
            'state.status' => trans('Trạng thái'),
        ];
    }

    public function save(string $status)
    {
        $requiredPermission = $this->purchase->exists ? 'vendors.purchases.edit' : 'vendors.purchases.create';
        if (! auth()->user()?->can($requiredPermission)) {
            $message = $this->purchase->exists
                ? __('Bạn không có quyền chỉnh sửa phiếu nhập.')
                : __('Bạn không có quyền tạo phiếu nhập.');
            session()->flash('error', $message);

            return redirect(route('vendors.purchases.index'));
        }

        $this->state['status'] = $status;

        if (empty($this->state['user_created_id'])) {
            $this->state['user_created_id'] = auth()->id();
        }

        if (empty($this->state['branch_id'])) {
            $this->state['branch_id'] = user_branch();
        }

        $this->validate(
            $this->rules(),
            [],
            $this->validationAttributes()
        );

        try {
            \Illuminate\Support\Facades\DB::transaction(function () {
                // --- Revert previous state if editing an existing successful purchase ---
                if ($this->purchase->exists) {
                    $originalPurchase = Purchase::with('products.product')->find($this->purchase->id);
                    if ($originalPurchase && in_array($originalPurchase->status, ['success', 'completed', 'paid'])) {
                        // 1. Silently revert stock (no log entries created)
                        foreach ($originalPurchase->products as $item) {
                            change_product_amount($item->product_id, $item->amount, false, $originalPurchase->branch_id);
                        }

                        // 2. Delete old product_logs for this purchase (clean slate)
                        \Polirium\Modules\Product\Http\Model\ProductLog::where('productable_type', Purchase::class)
                            ->where('productable_id', $originalPurchase->id)
                            ->delete();

                        // 3. Revert Vendor Stats
                        if ($originalPurchase->vendor_id) {
                            $vendor = Vendor::find($originalPurchase->vendor_id);
                            if ($vendor) {
                                $vendor->decrement('debt', ((float) $originalPurchase->need_pay - (float) $originalPurchase->value));
                                $vendor->decrement('total', $originalPurchase->need_pay);
                                $vendor->decrement('total_purchase', $originalPurchase->need_pay);
                            }
                        }
                    }
                }
                // -----------------------------------------------------------------------

                $this->purchase->fill($this->state);
                $this->purchase->save();

                $this->purchase->products()->delete();

                foreach ($this->products as $key => $value) {
                    $product = $value['product'];

                    if ($this->purchase->status === 'success') {
                        product_logs(
                            $value['product_id'],
                            $this->purchase->id,
                            Purchase::class,
                            $value['amount'],
                            $product['cost'],
                            $value['value'],
                        );
                    }

                    unset($value['product']);
                    $value['vendor_purchase_id'] = $this->purchase->id;
                    PurchaseProduct::create($value);
                }

                if ($this->purchase->status === 'success' && $this->purchase->vendor_id) {
                    Vendor::findOrFail($this->purchase->vendor_id)->increment('debt', ((float) $this->purchase->need_pay - (float) $this->purchase->value));
                    Vendor::findOrFail($this->purchase->vendor_id)->increment('total', $this->purchase->need_pay);
                    Vendor::findOrFail($this->purchase->vendor_id)->increment('total_purchase', $this->purchase->need_pay);
                }

                // Auto-create Cash Book Payment Voucher (Phiếu Chi)
                if ($this->purchase->status === 'success' && $this->purchase->value > 0) {
                    $exists = \Polirium\Modules\Accounting\Http\Model\Payment::where('finance_type', Purchase::class)
                        ->where('finance_id', $this->purchase->id)
                        ->exists();

                    if (! $exists) {
                        $accountingType = \Polirium\Modules\Accounting\Http\Model\AccountingType::firstOrCreate(
                            ['name' => 'Chi nhập hàng'],
                            ['type' => 'payment']
                        );

                        \Polirium\Modules\Accounting\Http\Model\Payment::create([
                            'code' => code_generate('PC', \Polirium\Modules\Accounting\Http\Model\Payment::max('id') ?? 0),
                            'branch_id' => $this->purchase->branch_id,
                            'date' => now(),
                            'type_id' => $accountingType->id,
                            'value' => $this->purchase->value,
                            'user_id' => $this->purchase->user_created_id,
                            'user_created_id' => auth()->id(),
                            'finance_type' => Purchase::class,
                            'finance_id' => $this->purchase->id,
                            'note' => 'Chi tiền nhập hàng đơn ' . $this->purchase->code,
                            'business_result' => 1,
                        ]);
                    }
                }
            });
        } catch (\Exception $e) {
            session()->flash('error', 'Có lỗi xảy ra khi lưu phiếu nhập: ' . $e->getMessage());

            return redirect(route('vendors.purchases.index'));
        }

        $this->resetInput();

        return redirect(route('vendors.purchases.index'));
    }
}
