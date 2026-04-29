<?php

namespace Polirium\Modules\Accounting\Http\Livewire\Index\Modal;

use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Polirium\Core\Base\Http\Models\Branch\Branch;
use Polirium\Core\Base\Http\Models\User;
use Polirium\Core\UI\Facades\Assets;
use Polirium\Modules\Accounting\Http\Model\AccountingType;
use Polirium\Modules\Accounting\Http\Model\Payment;
use Polirium\Modules\Accounting\Http\Model\PayPerson;
use Polirium\Modules\Accounting\Http\Model\Receipt;
use Polirium\Modules\Customer\Http\Model\Customer;
use Polirium\Modules\Vendor\Http\Model\Vendor;

class ModalCreateAccountingComponent extends Component
{
    public string $header_modal = '';

    public ?int $accounting_id = null;

    public array $lists = [
        'branches' => [],
        'users' => [],
        'types' => [],
        'finance_types' => [],
        'target_searched' => [],
    ];

    public array $input = [
        'branch_id' => null,
        'code' => '',
        'date' => null,
        'type_id' => null,
        'value' => 0,
        'user_id' => null,
        'user_created_id' => null,
        'finance_type' => null,
        'finance_id' => null,
        'business_result' => false,
        'note' => null,
    ];

    public string $model_class;

    public string $type = 'receipt';

    public ?string $search_target = null;

    protected function rules(): array
    {
        $table = (new $this->model_class())->getTable();

        return [
            'input.branch_id' => ['required', 'numeric', 'integer'],
            'input.code' => ['required', 'string', 'max:191', "unique:{$table},code,{$this->accounting_id},id"],
            'input.date' => ['required', 'date'],
            'input.type_id' => ['nullable', 'numeric', 'integer'],
            'input.value' => ['nullable', 'numeric'],
            'input.user_id' => ['required', 'numeric', 'integer'],
            'input.user_created_id' => ['required', 'numeric', 'integer'],
            'input.finance_type' => ['nullable', 'string', 'max:191'],
            'input.finance_id' => ['nullable', 'numeric', 'integer'],
            'input.business_result' => ['nullable', 'boolean'],
            'input.note' => ['nullable', 'string', 'max:191'],
        ];
    }

    public function mount()
    {
        Assets::loadJs('accounting-main');
        $this->model_class = 'Polirium\\Modules\\Accounting\\Http\\Model\\Receipt';

        $this->lists['branches'] = Branch::query()->select(['id', 'name'])->pluck('name', 'id')->all();
        $this->lists['users'] = User::query()->select(['id', 'name'])->pluck('name', 'id')->all();
        $this->lists['finance_types'] = [
            Customer::class => trans('modules/customer::customer.name'),
            User::class => trans('modules/accounting::accounting.staff'),
            Vendor::class => trans('modules/vendor::vendor.name'),
            PayPerson::class => trans('modules/accounting::accounting.pay_person.title'),
        ];
        $this->loadLists();
        $this->resetInput();
    }

    #[Computed]
    public function target()
    {
        $financeId = $this->input['finance_id'] ?? null;
        $financeType = $this->input['finance_type'] ?? null;

        if ($financeId && $financeType && class_exists($financeType)) {
            return $financeType::findOrFail($financeId);
        }

        return collect();
    }

    public function updatedInput($value, $key): void
    {
        if ($key === 'finance_type') {
            $this->reset('search_target');
            $this->lists['target_searched'] = [];
            $this->input['finance_id'] = null;
        }
    }

    public function updatedSearchTarget(): void
    {
        if ($this->search_target) {
            $financeType = $this->input['finance_type'] ?? null;
            if ($financeType && class_exists($financeType)) {
                $this->lists['target_searched'] = $financeType::query()
                ->where(function ($query) {
                    $query->where('name', 'like', "%{$this->search_target}%")
                    ->orWhere('phone', 'like', "%{$this->search_target}%");
                })
                ->limit(50)
                ->pluck('name', 'id')
                ->all();
            }
        } else {
            $this->lists['target_searched'] = [];
        }
    }

    public function render()
    {
        return view('modules/accounting::index.modal.modal-create-accounting');
    }

    public function resetInput()
    {
        $this->reset('search_target');
        $this->lists['target_searched'] = [];

        $code = $this->type === 'receipt' ? 'PT' : 'PC';

        $this->input = [
            'branch_id' => user_branch(),
            'code' => code_generate($code, $this->model_class::max('id')),
            'date' => date('Y-m-d'),
            'type_id' => null,
            'value' => 0,
            'user_id' => auth()->id(),
            'user_created_id' => auth()->id(),
            'finance_type' => null,
            'finance_id' => null,
            'business_result' => false,
            'note' => null,
        ];
    }

    #[On('show-modal-create-accounting')]
    public function showModal($model, string $type, ?int $id = null)
    {
        $this->authorize($id ? 'accountings.edit' : 'accountings.create');

        $this->header_modal = trans("modules/accounting::accounting.{$type}." . ($this->accounting_id ? 'edit' : 'create'));

        $this->accounting_id = $id;

        $this->type = $type;

        if ($type === 'payment') {
            $this->model_class = Payment::class;
        } else {
            $this->model_class = Receipt::class;
        }

        $this->resetInput();

        if ($id) {
            $model = $this->model_class::findOrFail($id);
            $this->input = $model->toArray();
        }

        $this->dispatch('modal', 'modal-create-accounting');
    }

    #[On('load-list-modal-create-accounting')]
    public function loadLists(): void
    {
        $this->lists['types'] = AccountingType::where('type', $this->type)->pluck('name', 'id')->all();
    }

    public function save()
    {
        $this->authorize($this->accounting_id ? 'accountings.edit' : 'accountings.create');

        $this->input['date'] = empty($this->input['date']) ? null : $this->input['date'];
        $this->input['business_result'] = empty($this->input['business_result']) ? 0 : $this->input['business_result'];

        $this->validate();

        if ($this->accounting_id) {
            $model = $this->model_class::find($this->accounting_id);
            $model->update($this->input);
        } else {
            $this->model_class::create($this->input);
        }

        $this->resetInput();
        $this->dispatch('modal', 'modal-create-accounting', 'hide');
        $this->dispatch('pg:eventRefresh-table-accounting');
    }
}
