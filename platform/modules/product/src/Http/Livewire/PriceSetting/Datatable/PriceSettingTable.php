<?php

namespace Polirium\Modules\Product\Http\Livewire\PriceSetting\Datatable;

use Illuminate\Database\Eloquent\Builder;
use Polirium\Core\Support\Http\Livewire\Tables\BaseTable;
use Polirium\Datatable\Column;
use Polirium\Datatable\Components\SetUp\Exportable;
use Polirium\Datatable\Facades\PowerGrid;
use Polirium\Datatable\PowerGridFields;
use Polirium\Modules\Product\Http\Model\Product;

final class PriceSettingTable extends BaseTable
{
    public string $tableName = 'table-products-price-setting';

    public $tab = 1;

    protected function getListeners(): array
    {
        return array_merge(
            parent::getListeners(),
            [
                'refresh-datatable-products-price-setting' => '$refresh',
            ]
        );
    }

    public function setUp(): array
    {
        $this->showCheckBox();

        return [
            PowerGrid::exportable('export')->striped()->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV),
            PowerGrid::header()->showSearchInput()->showToggleColumns(),
            PowerGrid::footer()->showPerPage()->showRecordCount(),
            PowerGrid::detail()->showCollapseIcon()->collapseOthers()->view('modules/product::index.datatable.detail'),
        ];
    }

    public function datasource(): Builder
    {
        return Product::query()->with(['category', 'trademark', 'shelve'])
        ->when(user_branch(), function ($q) {
            $q->productBranch();
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
        ->add('cost_formatted', function (Product $product) {
            return auth()->user()?->can('products.view-cost') ? core_number_format($product->cost) : '***';
        })
        ->add('price_formatted', function (Product $product) {
            return core_number_format($product->price);
        });
    }

    public function columns(): array
    {
        return [
            Column::make(__('modules/product::product.product_code_column'), 'code')->sortable()->searchable(),
            Column::make(__('modules/product::product.product_name_column'), 'name')->sortable()->searchable(),
            Column::add()->title(__('modules/product::product.selling_price_column'))->field('price_formatted', 'price')->sortable()->searchable(),
            ...(auth()->user()?->can('products.view-cost') ? [Column::add()->title(__('modules/product::product.cost_price_column'))->field('cost_formatted', 'cost')->sortable()->searchable()] : []),
            Column::make(__('modules/product::product.stock'), 'id')->hidden(true, false)->sortable()->searchable(),
            Column::make(__('modules/product::product.product_group_column'), 'category.name')->hidden(true, false)->sortable()->searchable(),
            Column::make(__('modules/product::product.trademark_column'), 'trademark.name')->hidden(true, false)->sortable()->searchable(),
            Column::make(__('modules/product::product.location_column'), 'shelve.name')->hidden(true, false)->sortable()->searchable(),
            Column::make(__('modules/product::product.product_type_column'), 'type_name')->hidden(true, false)->sortable()->searchable(),
            Column::make(__('modules/product::product.min_stock_level'), 'min_quantity')->hidden(true, false)->sortable()->searchable(),
            Column::make(__('modules/product::product.max_stock_level'), 'max_quantity')->hidden(true, false)->sortable()->searchable(),
            Column::action(trans('core/base::general.action')),
        ];
    }

    public function filters(): array
    {
        return [
            // Có thể thêm filter chi nhánh ở đây nếu cần
        ];
    }

    public function actions(Product $row): array
    {
        return [
            // Có thể thêm nút chỉnh giá ở đây
        ];
    }

    public function copy(
        $id
    ): void {
        $this->authorize('products.price-setting');
        $old = Product::findOrFail($id);
        $new = $old->replicate();
        $new->save();

        $branches_id = $old->branches->pluck('id')->toArray();
        $new->branches()->sync($branches_id);
        $new->branches()->updateExistingPivot($branches_id, ['qty' => $old->qty]);

        $this->dispatch('refresh-datatable-products-price-setting');
    }

    public function delete(
        $id
    ): void {
        $this->authorize('products.price-setting');
        Product::destroy($id);
        $this->dispatch('refresh-datatable-products-price-setting');
    }
}
