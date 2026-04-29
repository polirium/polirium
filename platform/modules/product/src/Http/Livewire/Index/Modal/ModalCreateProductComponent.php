<?php

namespace Polirium\Modules\Product\Http\Livewire\Index\Modal;

use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Polirium\Core\Base\Http\Models\Branch\Branch;
use Polirium\Core\UI\Facades\Assets;
use Polirium\Modules\Product\Http\Model\Category;
use Polirium\Modules\Product\Http\Model\Product;
use Polirium\Modules\Product\Http\Model\ProductElement;
use Polirium\Modules\Product\Http\Model\ProductUnit;
use Polirium\Modules\Product\Http\Model\Shelve;
use Polirium\Modules\Product\Http\Model\Trademark;

class ModalCreateProductComponent extends Component
{
    protected $listeners = [
        'refresh-modal-create-product' => '$refresh',
    ];

    public $product_id = null;

    #[Rule([
        'product.code' => 'required|string|max:255',
        'product.name' => 'required|string|max:255',
        'product.category_id' => 'nullable|numeric|integer',
        'product.trademark_id' => 'nullable|numeric|integer',
        'product.shelve_id' => 'nullable|numeric|integer',
        'product.cost' => 'nullable|numeric',
        'product.price' => 'nullable|numeric',
        'product.qty' => 'nullable|numeric|integer',
        'product.weight' => 'nullable|numeric|integer',
        'product.weight_type' => 'required|string|max:255|in:gram,kg',
        'product.allows_sale' => 'nullable|boolean',
        'product.unit' => 'nullable|string|max:255',
        'product.min_quantity' => 'nullable|numeric',
        'product.max_quantity' => 'nullable|numeric',
        'product.description' => 'nullable|string',
        'product.note' => 'nullable|string',
        'product.user_id' => 'required|numeric|integer',
        'product.type' => 'required|string|max:255|in:product,service,combo',
    ])]
    public $product = [
        'code' => '',
        'name' => '',
        'category_id' => null,
        'trademark_id' => null,
        'shelve_id' => null,
        'cost' => 0,
        'price' => 0,
        'qty' => 0,
        'weight' => 0,
        'weight_type' => 'gram',
        'allows_sale' => true,
        'unit' => null,
        'min_quantity' => 0,
        'max_quantity' => 999999999,
        'description' => null,
        'note' => null,
        'user_id' => null,
        'type' => 'product',
    ];

    public $qty = 0;

    public $units = [];

    public $search;

    public $categories;

    public $trademarks;

    public $shelves;

    public $lists = [];

    public $elements = [];

    public $tab = 1;

    public $readonly = false;

    #[Rule([
        'branches' => 'nullable|array',
        'branches.*' => 'nullable|integer|numeric',
    ])]
    public $branches = [];

    public function mount()
    {
        Assets::loadJs('product-main');
        $this->loadList();
        $this->lists['branches'] = Branch::select(['id', 'name'])->pluck('name', 'id')->all() ?? [];
        $this->resetInputs();
    }

    public function updated($value)
    {
        $this->validateOnly($value);
    }

    public function updatedSearch($value)
    {
        if ($value) {
            $this->lists['products'] = Product::select(['id', 'name', 'code', 'unit'])->where('name', 'like', '%' . $value . '%')->get();
        } else {
            $this->lists['products'] = null;
        }
    }

    public function render()
    {
        return view('modules/product::index.modal.modal-create-product');
    }

    public function resetInputs()
    {
        $this->reset(
            'units',
            'search',
            'elements',
            'branches',
            'qty',
        );
        $this->readonly = false;
        $this->tab = 1;
        $this->product = [
            'code' => code_generate('HH', Product::max('id')),
            'name' => '',
            'category_id' => null,
            'trademark_id' => null,
            'shelve_id' => null,
            'cost' => 0,
            'price' => 0,
            'qty' => 0,
            'weight' => 0,
            'weight_type' => 'gram',
            'allows_sale' => true,
            'unit' => null,
            'min_quantity' => 0,
            'max_quantity' => 999999999,
            'description' => null,
            'note' => null,
            'user_id' => null,
            'type' => 'product',
        ];
    }

    public function loadList()
    {
        $this->reset('categories', 'trademarks', 'shelves');
        $this->categories = Category::select(['id', 'name', 'parent_id'])->whereNull('parent_id')->get();
        $this->trademarks = Trademark::select(['id', 'name'])->pluck('name', 'id')->all();
        $this->shelves = Shelve::select(['id', 'name'])->pluck('name', 'id')->all();
    }

    #[On('show-modal-create-product')]
    public function showModal($id = null, $type = null, $readonly = false)
    {
        $this->product_id = $id;
        $this->resetInputs();

        $this->readonly = $readonly;

        if ($type) {
            $this->product['type'] = $type;
        }

        if ($id) {
            $productModel = Product::findOrFail($id);
            $this->product = $productModel->toArray();
            $this->units = ProductUnit::select([
                'product_id',
                'name',
                'code',
                'conversion_value',
                'price',
                'allows_sale',
            ])->where('product_id', $id)->get()->toArray();
            $this->branches = $productModel->branches?->pluck('id')->toArray() ?? [];

            // Fetch danh sách ProductElement khi edit
            $this->loadProductElements($id);
        }

        $this->dispatch('modal', 'modal-create-product');
    }

    /**
     * Load danh sách ProductElement cho sản phẩm
     */
    private function loadProductElements($productId)
    {
        $productElements = ProductElement::where('product_id', $productId)
            ->with('element:id,name,code,price')
            ->get();

        $this->elements = [];

        foreach ($productElements as $element) {
            $this->elements[$element->element_id] = [
                'element_id' => $element->element_id,
                'product_id' => $element->product_id,
                'product' => $element->element,
                'qty' => $element->qty,
                'price' => $element->price ?? $element->element->price ?? 0,
            ];
        }
    }

    #[On('product-set-value')]
    public function setValue($col, $id)
    {
        $this->loadList();
        $this->product[$col] = $id;
    }

    public function addUnit()
    {
        if ($this->product['unit']) {
            $this->units[] = [
                'name' => null,
                'code' => code_generate('ĐVT', ProductUnit::max('id') + count($this->units)),
                'conversion_value' => 1,
                'price' => 0,
                'allows_sale' => 1,
            ];
        } else {
            $this->units = [];
        }
    }

    public function removeUnit($index)
    {
        unset($this->units[$index]);
    }

    public function addElement($productId)
    {
        $product = Product::find($productId);
        if ($product) {
            $elementKey = $productId;
            $this->elements[$elementKey] = [
                'product' => $product,
                'element_id' => $productId,
                'qty' => 1,
                'price' => $product->price ?? 0,
            ];
        }
    }

    public function removeElement($elementId)
    {
        unset($this->elements[$elementId]);
    }

    public function updatedElements($value, $key)
    {
        // Khi số lượng thay đổi, tính lại thành tiền
        if (str_contains($key, 'qty')) {
            $elementId = explode('.', $key)[0];
            if (isset($this->elements[$elementId])) {
                $qty = $this->elements[$elementId]['qty'] ?? 1;
                $price = $this->elements[$elementId]['product']['price'] ?? 0;
                $this->elements[$elementId]['price'] = $qty * $price;
            }
        }
    }

    public function save()
    {
        // Enforce permission: create or edit
        if (! $this->product_id && ! auth()->user()?->can('products.create')) {
            session()->flash('message', [
                'color' => 'danger',
                'message' => 'Bạn không có quyền tạo sản phẩm.',
            ]);

            return;
        }

        if ($this->product_id && ! auth()->user()?->can('products.edit')) {
            session()->flash('message', [
                'color' => 'danger',
                'message' => 'Bạn không có quyền chỉnh sửa sản phẩm.',
            ]);

            return;
        }

        DB::beginTransaction();

        try {
            $this->product['user_id'] = auth()->id();

            $this->validate();

            // Convert array to model for save
            if ($this->product_id) {
                $productModel = Product::findOrFail($this->product_id);
                $productModel->fill($this->product);
            } else {
                $productModel = new Product($this->product);
            }
            $productModel->save();

            $branches = [];

            if (count($this->branches) <= 0) {
                $branches = key($this->lists['branches']);
            } else {
                $branches = $this->branches;
            }

            if (! $this->product_id) {
                $productModel->branches()->sync($branches);
                $productModel->branches()->updateExistingPivot($branches, ['qty' => $this->product['qty'] ?? 0]);
            } else {
                $productModel->branches()->syncWithoutDetaching($branches);
            }

            if ($this->product['unit'] && count($this->units) > 0) {
                $productModel->units()->delete();
                foreach ($this->units as $value) {
                    $value['product_id'] = $productModel->id;
                    ProductUnit::create($value);
                }
            }

            // Lưu elements
            if (count($this->elements) > 0) {
                ProductElement::where('product_id', $productModel->id)->delete();
                foreach ($this->elements as $element) {
                    $element['product_id'] = $productModel->id;
                    unset($element['product']);
                    ProductElement::create($element);
                }
            }

            DB::commit();

            $this->resetInputs();

            $this->dispatch('modal', 'modal-create-product', 'hide');

            $this->dispatch('refresh-datatable-products');
        } catch (\Throwable $th) {
            DB::rollBack();
            session()->flash('message', [
                'color' => 'danger',
                'message' => $th->getMessage(),
            ]);
        }
    }
}
