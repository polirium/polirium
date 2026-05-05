<?php

namespace Polirium\Modules\Vendor\Http\Livewire\Transfer;

use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Polirium\Core\Base\Http\Models\Branch\Branch;
use Polirium\Modules\Product\Http\Facades\ProductHelper;
use Polirium\Modules\Product\Http\Model\Product;
use Polirium\Modules\Vendor\Http\Model\Transfer\Transfer;
use Polirium\Modules\Vendor\Http\Model\Transfer\TransferProduct;

class TransferComponent extends Component
{
    public ?int $transfer_id = null;

    public Transfer $transfer;

    #[Rule([
        'products' => ['required', 'array'],
        'products.*.product_id' => ['required', 'numeric', 'integer'],
        'products.*.vendor_transfer_id' => ['nullable', 'numeric', 'integer'],
        'products.*.amount' => ['required', 'numeric', 'integer'],
        'products.*.price' => ['required', 'numeric'],
        'products.*.value' => ['required', 'numeric'],
        'products.*.note' => ['nullable', 'string', 'max:191'],
    ])]
    public array $products = [];

    public array $state = [];

    public array $lists = [
        'products' => [],
    ];

    public string $search = "";

    protected function rules() : array
    {
        $table = (new Transfer)->getTable();

        $statuses = trans('modules/vendor::transfer.status');
        $statuses = implode(',', array_keys($statuses));

        return [
            'state.code' => ['required', 'string', 'max:191', "unique:{$table},code,{$this->transfer_id},id"],
            'state.branch_id' => ['required', 'numeric', 'integer'],
            'state.form_branch_id' => ['required', 'numeric', 'integer'],
            'state.to_branch_id' => ['required', 'numeric', 'integer'],
            'state.user_created_id' => ['required', 'numeric', 'integer'],
            'state.status' => ['required', 'string', "in:{$statuses}"],
            'state.note' => ['nullable', 'string', 'max:191'],
            'state.date_send' => ['required', 'date'],
            'state.date_take' => ['nullable', 'date'],
        ];
    }

    public function mount() : void
    {
        $this->lists['branches'] = Branch::select(["id", "name"])
        ->where('id', '!=', user_branch())
        ->pluck('name', 'id')
        ->all() ?? [];
        $this->resetInput();
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

        if ($col === 'amount') {
            if ((int)$value <= 0) {
                $this->products[$id]['amount'] = 1;
            }

            $product = Product::find($id);

            if ($value > (int)$product?->amount) {
                $this->products[$id]['amount'] = 1;
            }
        }

        $this->products[$id]['value'] = (float)$this->products[$id]['price'] * (float)$this->products[$id]['amount'];

        $this->updatedTransfer();
    }

    public function updatedTransfer(mixed $value = null, string $key = null) : void
    {
        $total = array_sum(array_column($this->products, 'value'));

        // $this->transfer->total = $total;
    }

    public function render()
    {
        return view('modules/vendor::transfer.transfer.view');
    }

    public function resetInput() : void
    {
        $this->reset('transfer', 'products', 'search');

        if ($this->transfer_id) {
            $this->transfer = Transfer::with(['products.product'])->findOrFail($this->transfer_id);
            $this->state = $this->transfer->toArray();

            $this->products = $this->transfer->products
            ->transform(function ($item) {
                $item->product->amount = $item->product->amount;
                return $item;
            })
            ->toArray();
        } else {
            $this->transfer = new Transfer;
            $this->state = [
                'code' => code_generate('CH', Transfer::max('id')),
                'branch_id' => user_branch(),
                'form_branch_id' => user_branch(),
                'user_created_id' => auth()->id(),
                'date_send' => date('Y-m-d'),
                'status' => 'temp',
                'note' => '',
                'to_branch_id' => null, // Initialize specifically if needed
                'date_take' => null,
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
            'amount' => 1,
            'price' => $product->cost,
            'value' => $product->cost,
            'note' => null,
            'product' => $product
        ];

        $this->updatedTransfer();
    }

    public function removeProduct($id) : void
    {
        unset($this->products[$id]);
    }

    public function save(string $status)
    {
        $permission = $this->transfer_id ? 'vendors.transfers.edit' : 'vendors.transfers.create';
        if (! auth()->user()->can($permission)) {
            $this->dispatch('error', __('Bạn không có quyền thực hiện thao tác này.'));

            return;
        }

        $this->state['status'] = $status;

        $this->validate();

        if ($this->state['status'] === 'success') {
            $this->state['date_take'] = now();
        }

        $this->transfer->fill($this->state);
        $this->transfer->save();

        $this->transfer->products()->delete();

        foreach ($this->products as $key => $value) {
            $product = $value['product'];
            $product_id = $value['product_id'];

            unset($value['product']);
            $value['vendor_transfer_id'] = $this->transfer->id;
            TransferProduct::create($value);

            if ($this->transfer->status === 'success') {
                product_logs(
                    $product_id,
                    $this->transfer->id,
                    Transfer::class,
                    $value['amount'],
                    0,
                    0,
                    false,
                    $this->transfer->form_branch_id
                );

                change_product_amount($product_id, $value['amount'], true, $this->transfer->to_branch_id);
            }
        }

        $this->resetInput();

        return redirect(route('vendors.transfers.index'));
    }
}
