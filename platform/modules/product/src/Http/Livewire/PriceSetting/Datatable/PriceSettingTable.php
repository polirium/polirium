<?php

namespace Polirium\Modules\Product\Http\Livewire\PriceSetting\Datatable;

use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use Polirium\Core\Support\Http\Livewire\Tables\BaseTable;
use Polirium\Datatable\Column;
use Polirium\Datatable\Components\SetUp\Exportable;
use Polirium\Datatable\Facades\PowerGrid;
use Polirium\Datatable\PowerGridFields;
use Polirium\Modules\Product\Http\Model\Product;

/**
 * Price list: the product list with selling prices edited in place, the
 * product detail on row click and the standard row actions.
 */
final class PriceSettingTable extends BaseTable
{
    public string $tableName = 'table-products-price-setting';

    /** Name filter from the sidebar (the toolbar search is $search). */
    public string $nameFilter = '';

    protected function getListeners(): array
    {
        return array_merge(
            parent::getListeners(),
            [
                'refresh-datatable-products-price-setting' => '$refresh',
                // Saved from the product modal opened by "edit".
                'refresh-datatable-products' => '$refresh',
            ]
        );
    }

    public function setUp(): array
    {
        $this->showCheckBox();

        return [
            PowerGrid::exportable('export')->striped()->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV),
            PowerGrid::header()->showSearchInput()->showToggleColumns(),
            PowerGrid::footer()->showPerPage(25)->showRecordCount(),
            PowerGrid::detail()->collapseOthers()->view('modules/product::index.datatable.detail'),
        ];
    }

    public function datasource(): Builder
    {
        return Product::query()->with(['category', 'trademark', 'shelve'])
            ->when(user_branch(), fn ($query) => $query->productBranch())
            ->when(filled($this->nameFilter), fn ($query) => $query->where('name', 'like', '%' . addcslashes($this->nameFilter, '%_\\') . '%'))
            ->orderByDesc('id');
    }

    /**
     * Name search from the filter sidebar.
     */
    #[On('datatable-price-setting-filter')]
    public function filterSidebar(mixed $value, string $key): void
    {
        if ($key === 'name') {
            $this->nameFilter = mb_substr((string) $value, 0, 100);
            $this->resetPage();
        }
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
            ->add('cost_formatted', fn (Product $product) => auth()->user()?->can('products.view-cost') ? core_number_format($product->cost) : '***')
            ->add('row_actions', fn (Product $product) => view('modules/product::index.datatable.actions', ['id' => $product->id, 'type' => (string) $product->type])->render());
    }

    public function columns(): array
    {
        return [
            Column::make(__('modules/product::product.product_code_column'), 'code')->sortable()->searchable(),
            Column::make(__('modules/product::product.product_name_column'), 'name')->sortable()->searchable(),
            Column::make(__('modules/product::product.product_group_column'), 'category.name')->hidden(true, false),
            Column::make(__('modules/product::product.trademark_column'), 'trademark.name')->hidden(true, false),
            Column::make(__('modules/product::product.location_column'), 'shelve.name')->hidden(true, false),
            ...(auth()->user()?->can('products.view-cost') ? [Column::add()->title(__('modules/product::product.cost_price_column'))->field('cost_formatted', 'cost')->sortable()->bodyAttribute('text-end')->headerAttribute('text-end')] : []),
            Column::add()->title(__('modules/product::product.selling_price_column'))->field('price_input', 'price')->sortable()->visibleInExport(false)->bodyAttribute('pg-price-cell')->headerAttribute('text-end'),
            Column::add()->title(__('modules/product::product.selling_price_column'))->field('price_formatted', 'price')->hidden()->visibleInExport(true),
            Column::make(trans('core/base::general.action'), 'row_actions')->visibleInExport(false),
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

    /**
     * Copy from the row actions or the product detail.
     */
    #[On('triggerCopyProduct')]
    public function copyProduct($id): void
    {
        if (! auth()->user()->can('products.create')) {
            $this->dispatch('error', 'Bạn không có quyền tạo sản phẩm.');

            return;
        }

        // Copies the definition only (fresh uuid/code, combo components), never stock or history.
        Product::findOrFail($id)->duplicate();

        $this->dispatch('refresh-datatable-products-price-setting');
    }

    /**
     * Delete from the row actions or the product detail.
     */
    #[On('triggerRemoveProduct')]
    public function removeProduct($id): void
    {
        if (! auth()->user()->can('products.destroy')) {
            $this->dispatch('error', 'Bạn không có quyền xóa sản phẩm.');

            return;
        }

        try {
            Product::destroy($id);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('error', $e->validator->errors()->first());

            return;
        }

        $this->dispatch('refresh-datatable-products-price-setting');
    }
}
