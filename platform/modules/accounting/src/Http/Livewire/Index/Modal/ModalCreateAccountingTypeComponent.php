<?php

namespace Polirium\Modules\Accounting\Http\Livewire\Index\Modal;

use Livewire\Attributes\On;
use Livewire\Component;
use Polirium\Modules\Accounting\Http\Model\AccountingType;

class ModalCreateAccountingTypeComponent extends Component
{
    public ?int $accounting_type_id = null;

    public array $input = [
        'name' => '',
        'type' => 'receipt',
        'note' => null,
    ];

    protected function rules(): array
    {
        $table = (new AccountingType())->getTable();

        return [
            'input.name' => ['required', 'string', 'max:191', "unique:{$table},name,{$this->accounting_type_id},id"],
            'input.type' => ['required', 'string', 'max:191', 'in:receipt,payment'],
            'input.note' => ['nullable', 'string', 'max:191'],
        ];
    }

    public function mount()
    {
        $this->resetInput();
    }

    public function render()
    {
        return view('modules/accounting::index.modal.modal-create-accounting-type');
    }

    public function resetInput()
    {
        $this->input = [
            'name' => '',
            'type' => 'receipt',
            'note' => null,
        ];
    }

    #[On('show-modal-create-accounting-type')]
    public function showModal(string $type, ?int $id = null)
    {
        $this->accounting_type_id = $id;

        $this->resetInput();

        $this->input['type'] = $type;

        if ($id) {
            $typeModel = AccountingType::findOrFail($id);
            $this->input = $typeModel->toArray();
        }

        $this->dispatch('modal', 'modal-create-accounting-type');
    }

    public function save()
    {
        $this->authorize('accountings.edit');

        $this->validate();

        if ($this->accounting_type_id) {
            $typeModel = AccountingType::find($this->accounting_type_id);
            $typeModel->update($this->input);
        } else {
            AccountingType::create($this->input);
        }

        $this->resetInput();
        $this->dispatch('modal', 'modal-create-accounting-type', 'hide');
        $this->dispatch('pg:eventRefresh-table-accounting');
        $this->dispatch('load-list-modal-create-accounting');
    }
}
