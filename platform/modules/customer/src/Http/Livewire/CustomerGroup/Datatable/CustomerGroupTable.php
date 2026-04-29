<?php

namespace Polirium\Modules\Customer\Http\Livewire\CustomerGroup\Datatable;

use Illuminate\Database\Eloquent\Builder;
use Polirium\Core\Support\Http\Livewire\Tables\BaseTable;
use Polirium\Datatable\Button;
use Polirium\Datatable\Column;
use Polirium\Datatable\Components\SetUp\Exportable;
use Polirium\Datatable\Facades\PowerGrid;
use Polirium\Datatable\PowerGridFields;
use Polirium\Modules\Customer\Http\Model\CustomerGroup;

final class CustomerGroupTable extends BaseTable
{
    public string $tableName = 'table-customer-groups';

    public string $bulkDeletePermission = 'customers.groups';

    public array $request = [];

    public function setUp(): array
    {
        $this->showCheckBox();

        return [
            PowerGrid::exportable('export')->striped()->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV),

            PowerGrid::header()->showSearchInput()->showToggleColumns()->includeViewOnTop('modules/customer::customer-group.datatable.header'),
            PowerGrid::footer()->showPerPage()->showRecordCount()->includeViewOnBottom('modules/customer::customer-group.datatable.footer'),
            // PowerGrid::detail()->showCollapseIcon()->collapseOthers()->view('modules/vendor::index.datatable.detail'),
        ];
    }

    public function header(): array
    {
        return [];
    }

    public function datasource(): Builder
    {
        return CustomerGroup::query()
        ->orderByDesc('id');
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields();
    }

    public function columns(): array
    {
        return [
            Column::make(trans('core/base::general.id'), 'id')->sortable()->searchable(),
            Column::make(trans('modules/customer::customer.group.name'), 'name')->sortable()->searchable(),
            Column::action(trans('core/base::general.action')),
        ];
    }

    public function filters(): array
    {
        return [
            // Filter::inputText('username')->operators(['contains']),
        ];
    }

    public function actions(CustomerGroup $row): array
    {
        return [
            Button::add('edit-modal-create-customer-group')
                ->slot(tabler_icon('pencil', ['class' => 'icon']))
                ->id()
                ->class('btn btn-primary btn-icon btn-sm me-1')
                ->attributes(['aria-label' => trans('modules/customer::customer.group.edit')])
                ->tooltip(trans('modules/customer::customer.group.edit'))
                ->dispatch('show-modal-create-customer-group', ['id' => $row->id]),
        ];
    }
}
