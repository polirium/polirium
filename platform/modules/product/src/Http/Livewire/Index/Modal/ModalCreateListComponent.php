<?php

namespace Polirium\Modules\Product\Http\Livewire\Index\Modal;

use Livewire\Attributes\On;
use Livewire\Component;

class ModalCreateListComponent extends Component
{
    public $update_id = null;

    public $input;

    public $title;

    public $tbl = 'trademarks';

    public $model = 'Trademark';

    protected function rules()
    {
        return [
            'input.name' => "required|unique:{$this->tbl},name,{$this->update_id},id",
            'input.user_id' => 'required|numeric|integer',
        ];
    }

    public function mount()
    {
        $this->resetInput();
    }

    public function updated($value)
    {
        $this->validateOnly($value);
    }

    public function render()
    {
        return view('modules/product::index.modal.modal-create-list');
    }

    public function resetInput()
    {
        $this->reset('input');
        $model = "Polirium\\Modules\\Product\\Http\\Model\\{$this->model}";
        $this->input = new $model();
    }

    #[On('show-modal-create-list')]
    public function showModal($id = null, $title, $tbl = 'trademarks', $model = 'Trademark')
    {
        $this->title = $title;
        $this->tbl = $tbl;
        $this->model = $model;

        $this->update_id = $id;
        if ($id) {
            $modelClass = "Polirium\\Modules\\Product\\Http\\Model\\{$model}";
            $this->input = $modelClass::select(['id', 'name'])->findOrFail($id);
        } else {
            $this->resetInput();
        }
        $this->dispatch('modal', 'modal-create-list');
    }

    public function save()
    {
        $this->authorize($this->update_id ? 'products.edit' : 'products.create');

        $this->input->user_id = auth()->id();

        $this->validate();

        $this->input->save();

        $this->dispatch('refresh-modal-create-product');
        $this->dispatch('modal', 'modal-create-list', 'hide');
        $this->dispatch('product-set-value', mb_strtolower($this->model) . '_id', $this->input->id);
        $this->resetInput();
    }
}
