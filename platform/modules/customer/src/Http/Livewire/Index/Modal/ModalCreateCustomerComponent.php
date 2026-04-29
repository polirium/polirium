<?php

namespace Polirium\Modules\Customer\Http\Livewire\Index\Modal;

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

    public array $customer = [];

    public $customer_groups = [];

    public $list = [];

    protected function rules()
    {
        return [
            'customer.code' => 'required|string|max:255|unique:customers,code,' . ($this->customer_id ?? 'NULL') . ',id',
            'customer.name' => 'required|string|max:255',
            'customer.phone' => 'nullable|string|max:255|unique:customers,phone,' . ($this->customer_id ?? 'NULL') . ',id',
            'customer.phone2' => 'nullable|string|max:255|unique:customers,phone2,' . ($this->customer_id ?? 'NULL') . ',id',
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

    public function updated($name, $value)
    {
        // Handle JSON string from frontend for customer_groups
        if ($name === 'customer_groups' && is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $this->customer_groups = $decoded;
            }
        }

        $this->validateOnly($name);
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
        return view('modules/customer::index.modal.modal-create-customer');
    }

    public function resetInputs()
    {
        $this->reset('customer', 'customer_groups');

        // Generate unique customer code (auto increment until unique)
        $maxId = Customer::max('id') ?? 0;
        $code = code_generate('KH', $maxId);

        while (Customer::where('code', $code)->exists()) {
            $maxId++;
            $code = code_generate('KH', $maxId);
        }

        $this->customer = [
            'code' => $code,
            'sex' => 0,
            'type' => 0,
            'province_id' => null,
            'district_id' => null,
            'ward_id' => null,
            'address' => null,
            'phone' => null,
            'email' => null,
            'company' => null,
            'vat' => null,
            'facebook' => null,
            'note' => null,
            'birthday' => null,
            'name' => null,
            'phone2' => null,
        ];
    }

    #[On('refresh-customer-group-list')]
    public function refreshCustomerGroupList($newGroupId = null)
    {
        $this->list['customer-groups'] = CustomerGroup::select(['id', 'name'])->pluck('name', 'id')->all() ?: [];
        $this->dispatch('update-customer-group-options', options: $this->list['customer-groups'], newGroupId: $newGroupId);
    }

    #[On('show-modal-create-customer')]
    public function showModal($id = null, $name = null)
    {
        // Check permission
        if ($id) {
            $this->authorize('customers.edit');
        } else {
            $this->authorize('customers.create');
        }

        $this->list['customer-groups'] = CustomerGroup::select(['id', 'name'])->pluck('name', 'id')->all() ?: [];
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
            // Pre-fill customer info from search term
            if ($name) {
                // Detect if it's a phone number or name
                $cleanValue = preg_replace('/[^0-9]/', '', $name);
                if (strlen($cleanValue) >= 9 && strlen($cleanValue) <= 12 && preg_match('/^[0-9]+$/', $cleanValue)) {
                    // It's a phone number
                    $this->customer['phone'] = $name;
                } else {
                    // It's a name
                    $this->customer['name'] = $name;
                }
            }
        }
        $this->dispatch('modal', 'modal-create-customer');
    }

    public function openCreateGroupModal()
    {
        $this->dispatch('show-modal-create-customer-group');
    }

    public function save()
    {
        // Check permission
        if ($this->customer_id) {
            $this->authorize('customers.edit');
        } else {
            $this->authorize('customers.create');
        }

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

        // Dispatch event to select this customer in payment
        $this->dispatch('customer-created', customerId: $cust->id);

        $this->resetInputs();

        // Close modal and clean up backdrop
        $this->dispatch('modal', 'modal-create-customer', 'hide');
        $this->dispatch('cleanup-modal-backdrop');

        $this->dispatch('refresh-datatable-customers');
    }
}
