<?php

namespace Polirium\Modules\Accounting\Http\Livewire\Index\Datatable;

use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Polirium\Core\Support\Http\Livewire\Tables\BaseTable;
use Polirium\Datatable\Column;
use Polirium\Datatable\Components\SetUp\Exportable;
use Polirium\Datatable\Facades\PowerGrid;
use Polirium\Datatable\PowerGridFields;
use Polirium\Modules\Accounting\Http\Model\Payment;
use Polirium\Modules\Accounting\Http\Model\Receipt;

final class AccountingTable extends BaseTable
{
    public string $tableName = 'table-accounting';

    public array $request = [];

    public string $model;

    #[Url]
    public string $type = 'receipt';

    public function setUp(): array
    {
        $this->showCheckBox();

        if ($this->type === 'payment') {
            $this->model = Payment::class;
        } else {
            $this->model = Receipt::class;
        }

        return [
            PowerGrid::exportable('export')->striped()->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV),

            PowerGrid::header()->showSearchInput()->showToggleColumns()->includeViewOnTop('modules/accounting::index.datatable.header'),
            PowerGrid::footer()->showPerPage()->showRecordCount()->includeViewOnBottom('modules/accounting::index.datatable.footer'),
            PowerGrid::detail()
                ->showCollapseIcon()
                ->collapseOthers()
                ->view('modules/accounting::index.datatable.detail'),
        ];
    }

    public function header(): array
    {
        return [];
    }

    public function datasource(): Builder
    {
        return $this->model::query()
        ->when(user_branch(), function ($q) {
            $q->where('branch_id', user_branch()); // lấy theo chi nhánh đăng nhập
        })
        ->when(! empty($this->request['code']), function ($q) {
            $q->where('code', 'like', '%' . $this->request['code'] . '%');
        })
        ->when(! empty($this->request['type_id']), function ($q) {
            $q->where('type_id', $this->request['type_id']);
        })
        ->when(! empty($this->request['pay_person_id']), function ($q) {
            $q->where('pay_person_id', $this->request['pay_person_id']);
        })
        ->when(! empty($this->request['date_from']), function ($q) {
            $q->whereDate('date', '>=', $this->request['date_from']);
        })
        ->when(! empty($this->request['date_to']), function ($q) {
            $q->whereDate('date', '<=', $this->request['date_to']);
        })
        ->when(! empty($this->request['value_min']), function ($q) {
            $q->where('value', '>=', $this->request['value_min']);
        })
        ->when(! empty($this->request['value_max']), function ($q) {
            $q->where('value', '<=', $this->request['value_max']);
        })
        ->orderByDesc('id');
    }

    #[On('accounting-search-sidebar')]
    public function searchSidebar(mixed $value, string $key): void
    {
        $this->request[$key] = $value;
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
        ->add('date_format', fn ($model) => core_format_date($model->date))
        ->add('type_name', fn ($model) => $model->type?->name)
        ->add('finance_name', function ($model) {
            try {
                return $model->finance?->name;
            } catch (\Throwable $e) {
                return null;
            }
        })
        ->add('value_format', fn ($model) => core_number_format($model->value))
        ->add('type_context', fn ($model) => $this->type)
        ->add('model_class', fn ($model) => get_class($model))
        ->add('branch_name', fn ($model) => $model->branch?->name)
        ->add('user_created_name', fn ($model) => $model->userCreated?->name)
        ->add('user_name', fn ($model) => $model->user?->name);
    }

    public function columns(): array
    {
        return [
            Column::make(trans('core/base::general.id'), 'id')->sortable()->searchable(),
            Column::make(trans("modules/accounting::accounting.{$this->type}.code"), 'code')->sortable()->searchable(),
            Column::make(trans('modules/accounting::accounting.date'), 'date')->sortable()->searchable(),
            Column::make(trans('modules/accounting::accounting.type.name'), 'type_name')->sortable()->searchable(),
            Column::make(trans('modules/accounting::accounting.pay_person.name'), 'finance_name')->sortable()->searchable(),
            Column::make(trans('modules/accounting::accounting.value'), 'value_format')->sortable()->searchable(),
            Column::action(trans('core/base::general.action')),
        ];
    }

    public function filters(): array
    {
        return [
            // Filter::inputText('username')->operators(['contains']),
        ];
    }

    public function actions($row): array
    {
        return [];
    }

    public function updatedType($value): void
    {
        if ($value === 'payment') {
            $this->model = Payment::class;
        } else {
            $this->model = Receipt::class;
        }

        $this->js('window.location.reload();');
    }
}
