<?php

namespace Polirium\Modules\Vendor\Http\Livewire\Index\Datatable;

use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use Polirium\Core\Support\Http\Livewire\Tables\BaseTable;
use Polirium\Core\UI\Facades\Assets;
use Polirium\Datatable\Button;
use Polirium\Datatable\Column;
use Polirium\Datatable\Facades\PowerGrid;
use Polirium\Datatable\Facades\Rule;
use Polirium\Datatable\Components\SetUp\Exportable;
use Polirium\Datatable\PowerGridFields;
use Polirium\Modules\Product\Http\Model\Product;
use Polirium\Modules\Vendor\Http\Model\Vendor;

final class VendorTable extends BaseTable
{
    public string $tableName = 'table-vendors';

    public string $bulkDeletePermission = 'vendors.destroy';

    public $tab = 1;

    public array $request = [];

    public function mount(): void
    {
        parent::mount();
        Assets::loadCss('professional-table');
    }

    public function setUp(): array
    {
        $this->showCheckBox();

        return [
            PowerGrid::exportable('export')->striped()->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV),

            PowerGrid::header()->showSearchInput()->showToggleColumns()->includeViewOnTop('modules/vendor::index.datatable.header'),
            PowerGrid::footer()->showPerPage()->showRecordCount()->includeViewOnBottom('modules/vendor::index.datatable.footer'),
            // PowerGrid::detail()->showCollapseIcon()->collapseOthers()->view('modules/vendor::index.datatable.detail'),
        ];
    }


    public function datasource(): Builder
    {
        return Vendor::query()
        ->when(user_branch(), function ($q) {
            $q->where('branch_id', user_branch()); // lấy theo chi nhánh đăng nhập
        })
        ->when(isset_value($this->request['name']), function ($q) {
            $q->where('name', 'like', '%' . $this->request['name'] . '%');
        })
        ->when(isset_value($this->request['phone']), function ($q) {
            $q->where('phone', 'like', '%' . $this->request['phone'] . '%');
        })
        ->when(isset_value($this->request['email']), function ($q) {
            $q->where('email', 'like', '%' . $this->request['email'] . '%');
        })
        ->when(isset_value($this->request['address']), function ($q) {
            $q->where('address', 'like', '%' . $this->request['address'] . '%');
        })
        ->when(isset_value($this->request['group_id']), function ($q) {
            $q->whereRelation('group', 'vendor_group_id', $this->request['group_id']);
        })
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
            Column::make(trans('modules/vendor::vendor.name'), 'name')->sortable()->searchable(),
            Column::action(trans('core/base::general.action')),
        ];
    }

    public function filters(): array
    {
        return [
            // Filter::inputText('username')->operators(['contains']),
        ];
    }

    public function actions(Vendor $row): array
    {
        return [
            Button::add('edit-modal-create-vendor')
                ->slot(tabler_icon('pencil', ['class' => 'icon']))
                ->id()
                ->class('btn btn-primary btn-icon btn-sm')
                ->attributes(['aria-label' => trans('modules/vendor::vendor.edit')])
                ->tooltip(trans('modules/vendor::vendor.edit'))
                ->dispatch('show-modal-create-vendor', ['id' => $row->id]),
        ];
    }

    public function actionRules($row): array
    {
        return [
            Rule::button('edit-modal-create-vendor')
                ->when(fn($row) => !auth()->user()?->can('vendors.edit'))
                ->hide(),
        ];
    }

    #[On('vendor-search-sidebar')]
    public function searchSidebar(mixed $value, string $key) : void
    {
        $this->request[$key] = $value;
    }
}
