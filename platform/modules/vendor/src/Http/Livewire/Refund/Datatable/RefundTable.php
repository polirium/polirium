<?php

namespace Polirium\Modules\Vendor\Http\Livewire\Refund\Datatable;

use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use Polirium\Core\Support\Http\Livewire\Tables\BaseTable;
use Polirium\Datatable\Button;
use Polirium\Datatable\Column;
use Polirium\Datatable\Facades\PowerGrid;
use Polirium\Datatable\Components\SetUp\Exportable;
use Polirium\Datatable\PowerGridFields;
use Polirium\Modules\Product\Http\Model\Product;
use Polirium\Modules\Vendor\Http\Model\Purchase\Purchase;
use Polirium\Modules\Vendor\Http\Model\Refund\Refund;
use Polirium\Modules\Vendor\Http\Model\Vendor;

final class RefundTable extends BaseTable
{
    public string $tableName = 'table-refunds';

    public $tab = 1;

    public function setUp(): array
    {
        $this->showCheckBox();

        return [
            PowerGrid::exportable('export')->striped()->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV),

            PowerGrid::header()->showSearchInput()->showToggleColumns(),
            PowerGrid::footer()->showPerPage()->showRecordCount(),
            PowerGrid::detail()->showCollapseIcon()->collapseOthers()->view('modules/vendor::purchase.refund.datatable.detail'),
        ];
    }

    public function header(): array
    {
        return [];
    }

    public function datasource(): Builder
    {
        return Refund::query()
        ->when(user_branch(), function ($q) {
            $q->where('branch_id', user_branch()); // lấy theo chi nhánh đăng nhập
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
            Column::make(trans('modules/vendor::purchase.refund.code'), 'code')->sortable()->searchable(),
            Column::action(trans('core/base::general.action')),
        ];
    }

    public function filters(): array
    {
        return [
            // Filter::inputText('username')->operators(['contains']),
        ];
    }

    // public function actions(Refund $row): array
    // {
    //     return [
    //         Button::add('edit-modal-create-vendor')
    //             ->slot(trans('modules/vendor::vendor.edit'))
    //             ->id()
    //             ->class('btn btn-warning btn-sm')
    //             ->dispatch('show-modal-create-vendor', ['id' => $row->id]),
    //     ];
    // }

    #[On('redirect-purchase-refund-view')]
    public function redirectPurchaseView()
    {
        return redirect(route('vendors.purchases.refund'));
    }

    public function delete(int|string $id): void
    {
        $this->authorize('vendors.refunds.delete');
        Refund::find($id)?->delete();
    }
}
