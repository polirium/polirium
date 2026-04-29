<?php

namespace Polirium\Modules\Product\Http\Livewire\Stock\Datatable;

use Illuminate\Database\Eloquent\Builder;
use Polirium\Core\Support\Http\Livewire\Tables\BaseTable;
use Polirium\Datatable\Column;
use Polirium\Datatable\Components\SetUp\Exportable;
use Polirium\Datatable\Facades\PowerGrid;
use Polirium\Datatable\PowerGridFields;
use Polirium\Modules\Product\Http\Model\Stock\Stock;

final class StockTable extends BaseTable
{
    public string $tableName = 'table-stocks';

    public string $bulkDeletePermission = 'products.stock.delete';

    protected int $tab = 1;

    protected function getListeners(): array
    {
        return array_merge(
            parent::getListeners(),
            [
                'refresh-datatable-stocks' => '$refresh',
            ]
        );
    }

    public function setUp(): array
    {
        $this->showCheckBox();

        return [
            PowerGrid::exportable('export')->striped()->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV),

            PowerGrid::header()->showSearchInput()->showToggleColumns()->includeViewOnTop('modules/product::stock.index.datatable.header'),
            PowerGrid::footer()->showPerPage()->showRecordCount(),
            PowerGrid::detail()->showCollapseIcon()->collapseOthers()->view('modules/product::stock.index.datatable.detail'),
        ];
    }

    public function datasource(): Builder
    {
        return Stock::query()
            ->when(user_branch(), function ($q) {
                // filter by branch
            })
            ->orderByDesc('id');
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('created_at_formatted', fn (Stock $model) => core_format_date($model->created_at))
            ->add('amount', fn (Stock $model) => core_number_format($model->amount))
            ->add('value', fn (Stock $model) => core_number_format($model->value))
            ->add('deviation', fn (Stock $model) => core_number_format($model->deviation))
            ->add('increase_deviation', fn (Stock $model) => core_number_format($model->increase_deviation))
            ->add('decrease_deviation', fn (Stock $model) => core_number_format($model->decrease_deviation));
    }

    public function columns(): array
    {
        return [
            Column::make(trans('modules/product::stock.code'), 'code')->sortable()->searchable(),

            Column::make(trans('modules/product::stock.created_at'), 'created_at_formatted', 'created_at')
                ->sortable(),

            Column::make(trans('modules/product::stock.amount'), 'amount')
                ->sortable(),

            Column::make(trans('modules/product::stock.value'), 'value')
                ->sortable(),

            Column::make(trans('modules/product::stock.deviation'), 'deviation')
                ->sortable(),

            Column::make(trans('modules/product::stock.increase_deviation'), 'increase_deviation')
                ->sortable(),

            Column::make(trans('modules/product::stock.decrease_deviation'), 'decrease_deviation')
                ->sortable(),

            Column::make(trans('core/base::general.note'), 'note')
                ->sortable(),

            Column::action(trans('core/base::general.action')),
        ];
    }

    public function filters(): array
    {
        return [];
    }

    public function actions(Stock $row): array
    {
        return [];
    }

    /**
     * Override BaseTable delete to properly revert product quantities
     * and clean up ProductLog entries before soft-deleting.
     */
    public function delete(int|string $id): void
    {
        $this->authorize('products.stock.delete');

        $stock = Stock::with('products')->find($id);

        if (! $stock) {
            return;
        }

        // Chỉ revert nếu phiếu đã completed (đã thay đổi qty)
        if ($stock->status === 'completed') {
            // Xóa ProductLog liên quan
            \Polirium\Modules\Product\Http\Model\ProductLog::where('productable_type', Stock::class)
                ->where('productable_id', $stock->id)
                ->delete();

            // Revert số lượng sản phẩm
            foreach ($stock->products as $item) {
                $quantityDifference = $item->quantity_difference ?? 0;
                if ($quantityDifference != 0) {
                    change_product_amount(
                        $item->product_id,
                        abs($quantityDifference),
                        $quantityDifference < 0, // reverse: nếu tăng thì giảm lại
                        $stock->branch_id
                    );
                }
            }
        }

        $stock->delete();
    }
}
