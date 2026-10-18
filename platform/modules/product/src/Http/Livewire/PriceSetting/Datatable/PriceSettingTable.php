<?php

namespace Polirium\Modules\Product\Http\Livewire\PriceSetting\Datatable;

use Illuminate\Database\Eloquent\Builder;
use Polirium\Core\Support\Http\Livewire\Tables\BaseTable;
use Polirium\Datatable\Column;
use Polirium\Datatable\Components\SetUp\Exportable;
use Polirium\Datatable\Facades\PowerGrid;
use Polirium\Datatable\PowerGridFields;
use Polirium\Modules\Product\Http\Model\Product;

/**
 * Price list: selling prices edited in place, one row per product.
 */
final class PriceSettingTable extends BaseTable
{
    public string $tableName = 'table-products-price-setting';

    public function setUp(): array
    {
        return [
            PowerGrid::exportable('export')->striped()->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV),
            PowerGrid::header()->showSearchInput()->showToggleColumns(),
            PowerGrid::footer()->showPerPage(25)->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return Product::query()->with(['category', 'trademark', 'shelve'])
            ->when(user_branch(), fn ($query) => $query->productBranch())
            ->orderByDesc('id');
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        $canEdit = (bool) auth()->user()?->can('products.price-setting');

        return PowerGrid::fields()
            ->add('price_input', fn (Product $product) => $canEdit
                ? view('modules/product::price-setting.price-input', ['product' => $product])->render()
                : core_number_format($product->price))
            ->add('price_formatted', fn (Product $product) => core_number_format($product->price))
            ->add('cost_formatted', fn (Product $product) => auth()->user()?->can('products.view-cost') ? core_number_format($product->cost) : '***');
    }

    public function columns(): array
    {
        return [
            Column::make(__('modules/product::product.product_code_column'), 'code')->sortable()->searchable(),
            Column::make(__('modules/product::product.product_name_column'), 'name')->sortable()->searchable(),
            Column::make(__('modules/product::product.product_group_column'), 'category.name')->hidden(true, false),
            Column::make(__('modules/product::product.trademark_column'), 'trademark.name')->hidden(true, false),
            ...(auth()->user()?->can('products.view-cost') ? [Column::add()->title(__('modules/product::product.cost_price_column'))->field('cost_formatted', 'cost')->sortable()->bodyAttribute('text-end')->headerAttribute('text-end')] : []),
            Column::add()->title(__('modules/product::product.selling_price_column'))->field('price_input', 'price')->sortable()->visibleInExport(false)->bodyAttribute('pg-price-cell')->headerAttribute('text-end'),
            Column::add()->title(__('modules/product::product.selling_price_column'))->field('price_formatted', 'price')->hidden()->visibleInExport(true),
        ];
    }

    /**
     * Save a selling price typed in the list. Returns the formatted price, or
     * null when the value is not a valid amount.
     */
    public function updatePrice(int $id, mixed $value): ?string
    {
        $this->authorize('products.price-setting');

        $digits = preg_replace('/\D/', '', (string) $value);

        if ($digits === '' || strlen($digits) > 15) {
            $this->dispatch('error', __('modules/product::product.price_invalid'));

            return null;
        }

        $product = Product::query()
            ->when(user_branch(), fn ($query) => $query->productBranch())
            ->find($id);

        if (! $product) {
            return null;
        }

        $product->price = (int) $digits;
        $product->save();

        $this->dispatch('success', __('modules/product::product.price_saved', ['code' => $product->code]));

        return core_number_format($product->price);
    }
}
