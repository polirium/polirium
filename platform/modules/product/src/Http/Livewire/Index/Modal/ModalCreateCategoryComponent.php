<?php

namespace Polirium\Modules\Product\Http\Livewire\Index\Modal;

use Livewire\Attributes\On;
use Livewire\Component;
use Polirium\Modules\Product\Http\Model\Category;

class ModalCreateCategoryComponent extends Component
{
    public $category_id = null;

    public $parents;

    public array $input_category = [
        'name' => '',
        'parent_id' => null,
        'user_id' => null,
    ];

    protected function rules()
    {
        return [
            'input_category.name' => "required|unique:categories,name,{$this->category_id},id",
            'input_category.parent_id' => 'nullable|numeric|integer',
            'input_category.user_id' => 'required|numeric|integer',
        ];
    }

    public function mount()
    {
        $this->loadList();
        $this->resetInput();
    }

    public function updated($value)
    {
        $this->validateOnly($value);
    }

    public function render()
    {
        return view('modules/product::index.modal.modal-create-category');
    }

    public function resetInput()
    {
        $this->input_category = [
            'name' => '',
            'parent_id' => null,
            'user_id' => null,
        ];
    }

    #[On('show-modal-create-category')]
    public function showModal($id = null)
    {
        $this->loadList();
        $this->category_id = $id;
        if ($id) {
            $categoryModel = Category::select(['id', 'name', 'parent_id'])->with('childs:id,name,parent_id')->findOrFail($id);
            $this->input_category = $categoryModel->toArray();
        } else {
            $this->resetInput();
        }
        $this->dispatch('modal', 'modal-create-category');
    }

    public function loadList()
    {
        $this->parents = Category::select(['id', 'name', 'parent_id'])->whereNull('parent_id')->get();
    }

    public function save()
    {
        $this->authorize($this->category_id ? 'products.edit' : 'products.create');

        $this->input_category['user_id'] = auth()->id();

        $this->validate();

        if (empty($this->input_category['parent_id'])) {
            $this->input_category['parent_id'] = null;
        }

        if ($this->category_id) {
            $category = Category::find($this->category_id);
            $category->update($this->input_category);
        } else {
            $category = Category::create($this->input_category);
        }

        $this->dispatch('product-set-value', 'category_id', $category->id);
        $this->dispatch('refresh-modal-create-product');
        $this->dispatch('modal', 'modal-create-category', 'hide');
        $this->loadList();
        $this->resetInput();
    }
}
