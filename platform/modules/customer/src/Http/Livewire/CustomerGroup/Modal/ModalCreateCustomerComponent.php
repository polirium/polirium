<?php

namespace Polirium\Modules\Customer\Http\Livewire\CustomerGroup\Modal;

use Livewire\Attributes\On;
use Livewire\Component;
use Polirium\Modules\Customer\Http\Model\Customer;
use Polirium\Modules\Customer\Http\Model\CustomerGroup;

class ModalCreateCustomerComponent extends Component
{
    protected $listeners = [
        'refresh-modal-create-customer' => '$refresh',
    ];

    public $customer_id = null;

    public array $customer = [
        'code' => '',
        'name' => null,
        'phone' => null,
        'phone2' => null,
        'birthday' => null,
        'sex' => 0,
        'address' => null,
        'province_id' => null,
        'district_id' => null,
        'ward_id' => null,
        'type' => 0,
        'company' => null,
        'vat' => null,
        'email' => null,
        'facebook' => null,
        'note' => null,
        'user_id' => null,
    ];

    public $customer_groups = [];

    public $list = [];

    protected function rules()
    {
        return [
            'customer.code' => "required|string|max:255|unique:customers,code,{$this->customer_id},id",
            'customer.name' => 'required|string|max:255',
            'customer.phone' => "nullable|string|max:255|unique:customers,phone,{$this->customer_id},id",
            'customer.phone2' => "nullable|string|max:255|unique:customers,phone2,{$this->customer_id},id",
            'customer.birthday' => 'nullable|date',
            'customer.sex' => 'nullable|boolean|in:0,1',
            'customer.address' => 'nullable|string',
            'customer.province_id' => 'nullable|numeric|integer',
            'customer.district_id' => 'nullable|numeric|integer',
            'customer.ward_id' => 'nullable|numeric|integer',
            'customer.type' => 'nullable|boolean|in:0,1',
            'customer.company' => 'nullable|string|max:255',
            'customer.vat' => 'nullable|string|max:255',
            'customer.email' => 'nullable|string|max:255|email',
            'customer.facebook' => 'nullable|string|max:255',
            'customer.note' => 'nullable|string|max:255',
            'customer_groups' => 'nullable|array',
            'customer_groups.*' => 'nullable|numeric|integer',
        ];
    }

    public function mount()
    {
        $this->list['customer-groups'] = CustomerGroup::select(['id', 'name'])->pluck('name', 'id')->all() ?: [];
        $this->list['provinces'] = get_provinces();
        $this->list['districts'] = [];
        $this->list['wards'] = [];
        $this->resetInputs();
    }

    public function updated($value)
    {
        $this->validateOnly($value);
    }

    public function updatedCustomer($value, $key)
    {
        if ($key == 'province_id') {
            if ($value) {
                $this->list['districts'] = get_districts($value);
                $this->list['wards'] = [];
            } else {
                $this->list['districts'] = [];
                $this->list['wards'] = [];
            }

            $this->customer['district_id'] = null;
            $this->customer['ward_id'] = null;
        } elseif ($key == 'district_id') {
            if ($value) {
                $this->list['wards'] = get_wards($value);
            } else {
                $this->list['wards'] = [];
            }

            $this->customer['ward_id'] = null;
        }
    }

    public function render()
    {
        return view('modules/customer::customer-group.modal.modal-create-customer');
    }

    public function resetInputs()
    {
        $this->reset('customer_groups');
        $this->customer = [
            'code' => code_generate('KH', Customer::max('id')),
            'name' => null,
            'phone' => null,
            'phone2' => null,
            'birthday' => null,
            'sex' => 0,
            'address' => null,
            'province_id' => null,
            'district_id' => null,
            'ward_id' => null,
            'type' => 0,
            'company' => null,
            'vat' => null,
            'email' => null,
            'facebook' => null,
            'note' => null,
            'user_id' => null,
        ];
    }

    #[On('show-modal-create-customer')]
    public function showModal($id = null)
    {
        $this->authorize($id ? 'customers.edit' : 'customers.create');

        $this->list['customer-groups'] = CustomerGroup::select(['id', 'name'])->get() ?? [];
        $this->customer_id = $id;
        if ($id) {
            $customerModel = Customer::findOrFail($id);
            $this->customer = $customerModel->toArray();
            $this->customer_groups = $customerModel->customerGroups?->pluck('id')->toArray() ?? [];

            $district_id = $this->customer['district_id'];
            $ward_id = $this->customer['ward_id'];

            $this->updatedCustomer($this->customer['province_id'], 'province_id');
            $this->updatedCustomer($district_id, 'district_id');

            $this->customer['district_id'] = $district_id;
            $this->customer['ward_id'] = $ward_id;

        } else {
            $this->resetInputs();
        }
        $this->dispatch('modal', 'modal-create-customer');
    }

    public function save()
    {
        $this->authorize($this->customer_id ? 'customers.edit' : 'customers.create');

        $this->customer['user_id'] = auth()->id();
        $this->customer['birthday'] = ! empty($this->customer['birthday']) ? $this->customer['birthday'] : null;

        $this->validate();

        if ($this->customer_id) {
            $cust = Customer::find($this->customer_id);
            $cust->update($this->customer);
        } else {
            $cust = Customer::create($this->customer);
        }
        $cust->customerGroups()->sync($this->customer_groups ?? []);

        $this->resetInputs();

        $this->dispatch('modal', 'modal-create-customer', 'hide');

        $this->dispatch('refresh-datatable-customers');
    }
}
