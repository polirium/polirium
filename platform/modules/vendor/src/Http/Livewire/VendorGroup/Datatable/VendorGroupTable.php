<?php

namespace Polirium\Modules\Vendor\Http\Livewire\VendorGroup\Datatable;

use Illuminate\Database\Eloquent\Builder;
use Polirium\Core\Support\Http\Livewire\Tables\BaseTable;
use Polirium\Modules\Vendor\Http\Model\VendorGroup;
use Polirium\Datatable\Button;
use Polirium\Datatable\Column;
use Polirium\Datatable\Facades\PowerGrid;
use Polirium\Datatable\Components\SetUp\Exportable;
use Polirium\Datatable\PowerGridFields;

final class VendorGroupTable extends BaseTable
{
    public string $tableName = 'table-vendor-groups';

    public string $bulkDeletePermission = 'vendors.groups';

    public array $request = [];

    public function setUp(): array
    {
        $this->showCheckBox();

        return [
            PowerGrid::exportable('export')->striped()->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV),

            PowerGrid::header()->showSearchInput()->showToggleColumns()->includeViewOnTop('modules/vendor::vendor-group.datatable.header-actions'),
            PowerGrid::footer()->showPerPage()->showRecordCount()->includeViewOnBottom('modules/vendor::vendor-group.datatable.footer'),
        ];
    }

    public function datasource(): Builder
    {
        return VendorGroup::query()
        ->orderByDesc("id");
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
            Column::make(trans('modules/vendor::vendor.group.name'), 'name')->sortable()->searchable(),
            Column::action(trans('core/base::general.action')),
        ];
    }

    public function filters(): array
    {
        return [
        ];
    }

    public function actions(VendorGroup $row): array
    {
        return [
            Button::add('edit-modal-create-vendor-group')
                ->slot(tabler_icon('pencil', ['class' => 'icon']))
                ->id()
                ->class('btn btn-primary btn-icon btn-sm me-1')
                ->dispatch('show-modal-create-vendor-group', ['id' => $row->id]),
        ];
    }

    public function delete(int|string $id): void
    {
        $this->authorize('vendors.groups');
        VendorGroup::find($id)?->delete();
    }
}
