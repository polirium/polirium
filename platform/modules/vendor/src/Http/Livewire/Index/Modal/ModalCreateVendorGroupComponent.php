<?php

namespace Polirium\Modules\Vendor\Http\Livewire\Index\Modal;

use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Rule;
use Polirium\Core\UI\Facades\Assets;
use Polirium\Modules\Vendor\Http\Model\Vendor;
use Polirium\Modules\Vendor\Http\Model\VendorGroup;

class ModalCreateVendorGroupComponent extends Component
{
    public ?int $vendor_group_id = null;

    public array $input = [
        'name' => '',
        'user_created_id' => null,
        'note' => null,
    ];

    protected function rules()
    {
        $table = (new VendorGroup)->getTable();

        return [
            'input.name' => "required|string|max:191||unique:{$table},name,{$this->vendor_group_id},id",
            'input.user_created_id' => 'required|numeric|integer',
            'input.note' => 'nullable|string|max:191',
        ];
    }

    public function mount()
    {
        $this->resetInput();
    }

    public function render()
    {
        return view('modules/vendor::index.modal.modal-create-vendor-group');
    }

    public function resetInput()
    {
        $this->input = [
            'name' => '',
            'user_created_id' => auth()->id(),
            'note' => null,
        ];
    }

    #[On('show-modal-create-vendor-group')]
    public function showModal(?int $id = null)
    {
        $this->authorize('vendors.groups');
        $this->vendor_group_id = $id;
        $this->resetInput();

        if ($id) {
            $groupModel = VendorGroup::findOrFail($id);
            $this->input = $groupModel->toArray();
        }

        $this->dispatch("modal", "modal-create-vendor-group");
    }

    public function save()
    {
        $this->authorize('vendors.groups');
        $this->validate();

        if ($this->vendor_group_id) {
            $group = VendorGroup::find($this->vendor_group_id);
            $group->update($this->input);
        } else {
            VendorGroup::create($this->input);
        }

        $this->resetInput();

        // Use standard browser event dispatch for reliable modal closing
        $this->dispatch("close-modal-vendor-group");

        $this->dispatch('vendor-search-sidebar-refresh-lists');
        $this->dispatch('pg:eventRefresh-table-vendors');
        $this->dispatch('pg:eventRefresh-table-vendor-groups');
    }
}
