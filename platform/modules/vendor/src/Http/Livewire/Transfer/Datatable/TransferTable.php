<?php

namespace Polirium\Modules\Vendor\Http\Livewire\Transfer\Datatable;

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
use Polirium\Modules\Vendor\Http\Model\Transfer\Transfer;
use Polirium\Modules\Vendor\Http\Model\Vendor;

final class TransferTable extends BaseTable
{
    public string $tableName = 'table-transfers';

    public $tab = 1;

    public function setUp(): array
    {
        $this->showCheckBox();

        return [
            PowerGrid::exportable('export')->striped()->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV),

            PowerGrid::header()->showSearchInput()->showToggleColumns()->includeViewOnTop('modules/vendor::transfer.datatable.header'),
            PowerGrid::footer()->showPerPage()->showRecordCount(),
            PowerGrid::detail()->showCollapseIcon()->collapseOthers()->view('modules/vendor::transfer.index.datatable.detail'),
        ];
    }

    public function header(): array
    {
        return [];
    }

    public function datasource(): Builder
    {
        return Transfer::query()
        ->withCount('products')
        ->when(user_branch(), function ($q) {
            $q->where(function ($query) {
                $query->where('form_branch_id', user_branch())
                ->orWhere(function ($q) {
                    $q->orWhere('to_branch_id', user_branch())
                    ->where('status', '!=', 'temp');
                })
                ;
            });
        })
        ->orderByDesc("id");
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
        ->add('fromBranch_name', fn (Transfer $model) => $model->fromBranch?->name)
        ->add('toBranch_name', fn (Transfer $model) => $model->toBranch?->name)
        ;
    }

    public function columns(): array
    {
        return [
            Column::make(trans('core/base::general.id'), 'id')->sortable()->searchable(),
            Column::make(trans('modules/vendor::transfer.code'), 'code')->sortable()->searchable(),
            Column::make(trans('modules/vendor::transfer.from_branch'), 'fromBranch_name')->sortable()->searchable(),
            Column::make(trans('modules/vendor::transfer.to_branch'), 'toBranch_name')->sortable()->searchable(),
            Column::make(trans('modules/vendor::transfer.date_send'), 'date_send')->sortable()->searchable(),
            Column::make(trans('modules/vendor::transfer.date_take'), 'date_take')->sortable()->searchable(),
            Column::make(trans('modules/vendor::transfer.amount'), 'amount')->sortable()->searchable(),
            Column::make(trans('modules/vendor::transfer.amount_products'), 'amount_products')->sortable()->searchable(),
            ...(auth()->user()?->can('vendors.transfers.view-price') ? [Column::make(trans('modules/vendor::transfer.value'), 'value')->sortable()->searchable()] : []),
            Column::make(trans('core/base::general.status'), 'status_name')->sortable()->searchable(),
            Column::action(trans('core/base::general.action')),
        ];
    }

    public function filters(): array
    {
        return [
            // Filter::inputText('username')->operators(['contains']),
        ];
    }

    public function actions(Transfer $row): array
    {
        return [
            // Button::add('edit-modal-create-vendor')
            //     ->slot(trans('modules/vendor::vendor.edit'))
            //     ->id()
            //     ->class('btn btn-warning btn-sm')
            //     ->dispatch('show-modal-create-vendor', ['id' => $row->id]),
        ];
    }

    public string $bulkDeletePermission = 'vendors.transfers.delete';

    #[On('redirect-transfer-view')]
    public function redirectTransferView()
    {
        return redirect(route('vendors.transfers.transfer'));
    }

    public function delete(int|string $id): void
    {
        if (! auth()->user()->can('vendors.transfers.delete')) {
            $this->dispatch('error', 'Bạn không có quyền xóa phiếu chuyển hàng.');

            return;
        }

        $item = Transfer::with('products')->find($id);

        if (! $item) {
            return;
        }

        // Revert stock if status is success
        if ($item->status == 'success') {
            foreach ($item->products as $product) {
                // 1. Revert decrease at form_branch_id (so we INCREASE)
                product_logs(
                    $product->product_id,
                    $item->id,
                    Transfer::class,
                    $product->amount,
                    0,
                    0,
                    true, // INCREASE back at form_branch
                    $item->form_branch_id
                );

                // 2. Revert increase at to_branch_id (so we DECREASE)
                change_product_amount(
                    $product->product_id,
                    $product->amount,
                    false, // DECREASE at to_branch
                    $item->to_branch_id
                );
            }

            \Polirium\Modules\Product\Http\Model\ProductLog::where('productable_type', Transfer::class)
            ->where('productable_id', $item->id)
            ->delete();
        }

        $item->products()->delete();
        $item->delete();
    }
}
