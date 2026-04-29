<?php

namespace Polirium\Modules\Accounting\Http\Livewire\Index\Modal;

use Livewire\Attributes\On;
use Livewire\Component;
use Polirium\Modules\Accounting\Http\Model\PayPerson;

class ModalCreateAccountingPayPersonComponent extends Component
{
    public ?int $pay_person_id = null;

    public array $lists = [
        'districts' => [],
        'wards' => [],
    ];

    public PayPerson $input;

    protected function rules(): array
    {
        $table = (new PayPerson())->getTable();

        return [
            'input.name' => ['required', 'string', 'max:191', "unique:{$table},name,{$this->pay_person_id},id"],
            'input.address' => ['nullable', 'string', 'max:191'],
            'input.phone' => ['nullable', 'string', 'max:191'],
            'input.province_id' => ['nullable', 'numeric', 'integer'],
            'input.district_id' => ['nullable', 'numeric', 'integer'],
            'input.ward_id' => ['nullable', 'numeric', 'integer'],
            'input.note' => ['nullable', 'string', 'max:191'],
        ];
    }

    public function mount()
    {
        $this->lists['provinces'] = get_provinces();
        $this->resetInput();
    }

    public function updatedInput($value, $key)
    {
        if ($key == 'province_id') {
            if ($value) {
                $this->lists['districts'] = get_districts($value);
                $this->lists['wards'] = [];
            } else {
                $this->lists['districts'] = [];
                $this->lists['wards'] = [];
            }

            $this->input->district_id = null;
            $this->input->ward_id = null;
        } elseif ($key == 'district_id') {
            if ($value) {
                $this->lists['wards'] = get_wards($value);
            } else {
                $this->lists['wards'] = [];
            }

            $this->input->ward_id = null;
        }
    }

    public function render()
    {
        return view('modules/accounting::index.modal.modal-create-accounting-pay-person');
    }

    public function resetInput()
    {
        $this->reset('input');
        $this->input = new PayPerson();
    }

    #[On('show-modal-create-accounting-pay-person')]
    public function showModal(?int $id = null)
    {
        $this->pay_person_id = $id;

        $this->resetInput();

        if ($id) {
            $this->input = PayPerson::findOrFail($id);
        }

        $this->dispatch('modal', 'modal-create-accounting-pay-person');
    }

    public function save()
    {
        $this->authorize('accountings.edit');

        $this->validate();

        $this->input->save();

        $this->resetInput();
        $this->dispatch('modal', 'modal-create-accounting-pay-person', 'hide');
        $this->dispatch('pg:eventRefresh-table-accounting');
        $this->dispatch('load-lists-modal-create-accounting');
    }
}
