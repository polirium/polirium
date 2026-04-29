<?php

namespace Polirium\Modules\Vendor\Http\Livewire\Index\Modal;

use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Rule;
use Polirium\Modules\Vendor\Http\Model\Vendor;
use Polirium\Modules\Vendor\Http\Model\VendorGroup;

class ModalCreateVendorComponent extends Component
{
    public ?int $vendor_id = null;

    public array $lists = [
        'provinces' => [],
        'districts' => [],
        'wards' => [],
        'group' => [],
    ];

    public array $input = [
        'branch_id' => null,
        'code' => '',
        'name' => '',
        'vat' => '',
        'address' => '',
        'phone' => null,
        'email' => null,
        'province_id' => null,
        'district_id' => null,
        'ward_id' => null,
        'user_created_id' => null,
        'company' => null,
        'note' => null,
    ];

    #[Rule('nullable|array')]
    public array $group = [];

    protected function rules()
    {
        $table = (new Vendor)->getTable();

        return [
            'input.branch_id' => 'required|numeric|integer',
            'input.code' => "required|string|max:191||unique:{$table},code,{$this->vendor_id},id",
            'input.name' => "required|string|max:191||unique:{$table},name,{$this->vendor_id},id",
            'input.vat' => 'nullable|string|max:191',
            'input.address' => 'nullable|string|max:191',
            'input.phone' => 'nullable|string|max:11|min:10',
            'input.email' => 'nullable|string|max:191|email',
            'input.province_id' => 'nullable|numeric|integer',
            'input.district_id' => 'nullable|numeric|integer',
            'input.ward_id' => 'nullable|numeric|integer',
            'input.user_created_id' => 'required|numeric|integer',
            'input.company' => 'nullable|string|max:191',
            'input.note' => 'nullable|string|max:191',
        ];
    }

    public function mount()
    {
        $this->lists['provinces'] = get_provinces();

        $this->resetInput();
    }

    public function updatedInput(mixed $value, string $key)
    {
        if ($key == "province_id") {
            if ($value) {
                $this->lists["districts"] = get_districts($value);
                $this->lists["wards"] = [];
            } else {
                $this->lists["districts"] = [];
                $this->lists["wards"] = [];
            }
            $this->input['district_id'] = null;
            $this->input['ward_id'] = null;
        }

        if ($key == "district_id") {
            if ($value) {
                $this->lists["wards"] = get_wards($value);
            } else {
                $this->lists["wards"] = [];
            }
            $this->input['ward_id'] = null;
        }
    }

    public function render()
    {
        return view('modules/vendor::index.modal.modal-create-vendor');
    }

    public function resetInput()
    {
        $this->reset('group');
        $this->input = [
            'branch_id' => user_branch(),
            'code' => code_generate('NCC', Vendor::max('id')),
            'name' => '',
            'vat' => '',
            'address' => '',
            'phone' => null,
            'email' => null,
            'province_id' => null,
            'district_id' => null,
            'ward_id' => null,
            'user_created_id' => auth()->id(),
            'company' => null,
            'note' => null,
        ];
    }

    #[On('show-modal-create-vendor')]
    public function showModal(?int $id = null)
    {
        $this->authorize($id ? 'vendors.edit' : 'vendors.create');
        $this->vendor_id = $id;
        $this->resetInput();
        $this->lists['group'] = VendorGroup::select('name', 'id')->pluck('name', 'id')->all();

        if ($id) {
            $vendorModel = Vendor::findOrFail($id);
            $this->input = $vendorModel->toArray();

            $district_id = $this->input['district_id'];
            $ward_id = $this->input['ward_id'];

            $this->updatedInput($this->input['province_id'], "province_id");
            $this->input['district_id'] = $district_id;

            $this->updatedInput($this->input['district_id'], "district_id");
            $this->input['ward_id'] = $ward_id;

            $this->group = $vendorModel->group->pluck('id')->toArray();
        }

        $this->dispatch("modal", "modal-create-vendor");
    }

    public function save()
    {
        $this->authorize($this->vendor_id ? 'vendors.edit' : 'vendors.create');
        $this->validate();

        // Ensure defaults for required string columns
        $this->input['vat'] = $this->input['vat'] ?? '';
        $this->input['address'] = $this->input['address'] ?? '';

        if ($this->vendor_id) {
            $vendor = Vendor::find($this->vendor_id);
            $vendor->update($this->input);
        } else {
            $vendor = Vendor::create($this->input);
        }

        $vendor->group()->sync($this->group);

        $this->resetInput();
        $this->dispatch("modal", "modal-create-vendor", "hide");
        \Log::info('ModalCreateVendorComponent dispatched vendor-saved', ['id' => $vendor->id]);
        $this->dispatch('vendor-saved', id: $vendor->id);
        $this->dispatch('pg:eventRefresh-table-vendors');
    }
}
