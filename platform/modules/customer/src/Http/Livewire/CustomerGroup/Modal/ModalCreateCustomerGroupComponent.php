<?php

namespace Polirium\Modules\Customer\Http\Livewire\CustomerGroup\Modal;

use Livewire\Attributes\On;
use Livewire\Component;
use Polirium\Modules\Customer\Http\Model\CustomerGroup;

class ModalCreateCustomerGroupComponent extends Component
{
    protected $listeners = [
        'refresh-modal-create-customer-group' => '$refresh',
    ];

    public $item_id = null;

    public array $group = [
        'name' => '',
        'type' => 1,
        'note' => null,
        'user_id' => null,
    ];

    protected function rules()
    {
        return [
            'group.name' => "required|string|max:255|unique:customer_groups,name,{$this->item_id},id",
            'group.type' => 'nullable|integer|numeric|in:1,2,3',
            'group.note' => 'nullable|string|max:255',
        ];
    }

    public function mount()
    {
        $this->resetInputs();
    }

    public function updated($value)
    {
        $this->validateOnly($value);
    }

    public function render()
    {
        return view('modules/customer::customer-group.modal.modal-create-customer-group');
    }

    public function resetInputs()
    {
        $this->group = [
            'name' => '',
            'type' => 1,
            'note' => null,
            'user_id' => null,
        ];
    }

    #[On('show-modal-create-customer-group')]
    public function showModal($id = null)
    {
        $this->authorize('customers.groups');

        $this->item_id = $id;
        if ($id) {
            $groupModel = CustomerGroup::findOrFail($id);
            $this->group = $groupModel->toArray();
        } else {
            $this->resetInputs();
        }
        $this->dispatch('modal', 'modal-create-customer-group');
    }

    public function save()
    {
        $this->authorize('customers.groups');

        $this->group['user_id'] = auth()->id();
        $this->validate();

        if ($this->item_id) {
            $groupModel = CustomerGroup::find($this->item_id);
            $groupModel->update($this->group);
        } else {
            CustomerGroup::create($this->group);
        }

        $this->resetInputs();

        $this->dispatch('modal', 'modal-create-customer-group', 'hide');

        $this->dispatch('refresh-datatable-customers');

        $this->dispatch('customer-filter-load-list');
    }
}
