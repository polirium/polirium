<?php

namespace Polirium\Modules\Product\Http\Livewire\Index\Datatable;

use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use Polirium\Core\Support\Http\Livewire\Tables\BaseTable;
use Polirium\Datatable\Column;
use Polirium\Datatable\Facades\PowerGrid;
use Polirium\Datatable\PowerGridFields;
use Polirium\Modules\Product\Http\Model\Product;

class ProductListTable extends BaseTable
{
    public string $tableName = 'table-product-list';
    public string $sortField = 'products.id';
    public string $sortDirection = 'desc';

    public $tab = 1;

    public array $request = [];

    /**
     * Reset sort field if it points to a non-DB column (e.g. stock_formatted).
     */
    public function booted(): void
    {
        $nonSortableFields = ['stock_formatted', 'cost_formatted'];

        if (in_array($this->sortField, $nonSortableFields)) {
            $this->sortField = 'products.id';
            $this->sortDirection = 'desc';
        }

        if (property_exists($this, 'sortArray') && is_array($this->sortArray)) {
            foreach ($nonSortableFields as $field) {
                unset($this->sortArray[$field]);
            }
        }
    }

    #[On('triggerCopyProduct')]
    public function copyProduct(
        $id
    ): void {
        if (! auth()->user()->can('products.create')) {
            $this->dispatch('error', 'Bạn không có quyền tạo sản phẩm.');

            return;
        }

        $old = Product::findOrFail($id);
        $new = $old->replicate();
        $new->save();

        $branches_id = $old->branches->pluck('id')->toArray();
        $new->branches()->sync($branches_id);
        $new->branches()->updateExistingPivot($branches_id, ['qty' => $old->qty]);

        $this->dispatch('refresh-datatable-products');
    }

    #[On('triggerRemoveProduct')]
    public function removeProduct(
        $id
    ): void {
        if (! auth()->user()->can('products.destroy')) {
            $this->dispatch('error', 'Bạn không có quyền xóa sản phẩm.');

            return;
        }

        Product::destroy($id);
        $this->dispatch('refresh-datatable-products');
    }

    public function bulkDelete(): void
    {
        if (! auth()->user()->can('products.destroy')) {
            $this->dispatch('error', 'Bạn không có quyền xóa sản phẩm.');

            return;
        }

        if (empty($this->checkboxValues)) {
            $this->dispatch('error', 'Vui lòng chọn ít nhất một sản phẩm.');

            return;
        }

        $products = Product::whereIn('id', $this->checkboxValues)->get();

        if ($products->isEmpty()) {
            $this->dispatch('error', 'Không tìm thấy sản phẩm nào.');

            return;
        }

        $count = 0;
        foreach ($products as $product) {
            $product->delete();
            $count++;
        }

        $this->checkboxValues = [];
        $this->checkboxAll = false;

        $this->dispatch('success', "Đã xóa {$count} sản phẩm thành công.");
        $this->dispatch('refresh-datatable-products');
        $this->dispatch('pg:eventRefresh-table-product-list');
    }

    protected function getListeners(): array
    {
        return array_merge(
            parent::getListeners(),
            [
                'refresh-datatable-products' => '$refresh',
            ]
        );
    }

    public function exportExcelTemplate()
    {
        $query = $this->datasource();

        if (! empty($this->checkboxValues)) {
            $query->whereIn('products.id', $this->checkboxValues);
        }

        $products = $query->get();

        $templatePath = base_path('platform/modules/product/public/sample_import_products.xlsx');
        if (! file_exists($templatePath)) {
            $this->dispatch('error', 'Không tìm thấy file mẫu.');

            return;
        }

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($templatePath);
        $worksheet = $spreadsheet->getActiveSheet();

        // Remove 3 demo rows
        $worksheet->removeRow(2, 3);

        $rowNum = 2;
        foreach ($products as $i => $product) {
            $isService = $product->type === 'service';

            $stockQty = $isService ? 0 : ($product->stock_qty ?? 0);
            if ($stockQty >= 999999) {
                $stockQty = 0;
            }

            $minQty = $isService ? 0 : $product->min_quantity;
            $maxQty = $isService ? 0 : ($product->max_quantity >= 999999 ? 0 : $product->max_quantity);

            $worksheet->setCellValue("A{$rowNum}", $product->type_name);
            $worksheet->setCellValue("B{$rowNum}", $product->category?->name);
            $worksheet->setCellValue("C{$rowNum}", $product->code);
            $worksheet->setCellValue("D{$rowNum}", $product->name);
            $worksheet->setCellValue("E{$rowNum}", $product->trademark?->name);
            $worksheet->setCellValue("F{$rowNum}", $product->price);
            $worksheet->setCellValue("G{$rowNum}", auth()->user()?->can('products.view-cost') ? $product->cost : 0);
            $worksheet->setCellValue("H{$rowNum}", $stockQty);
            $worksheet->setCellValue("I{$rowNum}", 0); // KH đặt
            $worksheet->setCellValue("J{$rowNum}", '0 ngày');
            $worksheet->setCellValue("K{$rowNum}", $minQty);
            $worksheet->setCellValue("L{$rowNum}", $maxQty);
            $worksheet->setCellValue("M{$rowNum}", $product->unit);
            $worksheet->setCellValue("N{$rowNum}", 1);
            $worksheet->setCellValue("O{$rowNum}", '');
            $worksheet->setCellValue("P{$rowNum}", '');
            $worksheet->setCellValue("Q{$rowNum}", '');
            $worksheet->setCellValue("R{$rowNum}", ''); // Hình ảnh
            $worksheet->setCellValue("S{$rowNum}", $product->weight);
            $worksheet->setCellValue("T{$rowNum}", $product->status === 'published' ? 1 : 0);
            $worksheet->setCellValue("U{$rowNum}", $product->allows_sale);
            $worksheet->setCellValue("V{$rowNum}", $product->description);
            $worksheet->setCellValue("W{$rowNum}", $product->note);
            $worksheet->setCellValue("X{$rowNum}", $product->shelve?->name);
            $worksheet->setCellValue("Y{$rowNum}", '');
            $worksheet->setCellValue("Z{$rowNum}", $product->created_at ? $product->created_at->format('d/m/Y H:i') : '');

            // Formatting
            $worksheet->getStyle("F{$rowNum}:H{$rowNum}")->getNumberFormat()->setFormatCode('#,##0');

            if ($i % 2 === 0) {
                // Light blue row
                $worksheet->getStyle("A{$rowNum}:Z{$rowNum}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFDCE6F1');
            } else {
                // White row
                $worksheet->getStyle("A{$rowNum}:Z{$rowNum}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFFFFFFF');
            }

            // Borders
            $worksheet->getStyle("A{$rowNum}:Z{$rowNum}")->getBorders()
                ->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)
                ->getColor()->setARGB('FFB4C6E7');

            $rowNum++;
        }

        $tempFileName = 'export_products_' . time() . '.xlsx';
        $tempFile = storage_path('app/public/' . $tempFileName);
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($tempFile);

        return response()->download($tempFile, $tempFileName)->deleteFileAfterSend(true);
    }

    public function setUp(): array
    {
        $this->showCheckBox();

        return [
            PowerGrid::header()->showSearchInput()->showToggleColumns()->includeViewOnTop('modules/product::index.datatable.header'),
            PowerGrid::footer()->showPerPage()->showRecordCount(),
            PowerGrid::detail()->showCollapseIcon()->collapseOthers()->view('modules/product::index.datatable.detail'),
        ];
    }

    public function datasource(): Builder
    {
        \Illuminate\Support\Facades\Log::info('ProductListTable loaded', ['class' => __CLASS__, 'methods' => get_class_methods($this)]);

        $branchId = user_branch();

        return Product::query()
            ->leftJoin('categories as category', 'products.category_id', '=', 'category.id')
            ->leftJoin('trademarks as trademark', 'products.trademark_id', '=', 'trademark.id')
            ->leftJoin('shelves as shelve', 'products.shelve_id', '=', 'shelve.id')
            ->select('products.*')
            ->when($branchId, function ($q) use ($branchId) {
                $q->selectSub(
                    \Illuminate\Support\Facades\DB::table('product_branches')
                        ->selectRaw('COALESCE(SUM(qty), 0)')
                        ->whereColumn('product_branches.product_id', 'products.id')
                        ->where('product_branches.branch_id', $branchId),
                    'stock_qty'
                );
            }, function ($q) {
                $q->selectSub(
                    \Illuminate\Support\Facades\DB::table('product_branches')
                        ->selectRaw('COALESCE(SUM(qty), 0)')
                        ->whereColumn('product_branches.product_id', 'products.id'),
                    'stock_qty'
                );
            })
            ->with(['branches'])
            ->with(['category', 'trademark', 'shelve'])
            ->when($branchId, function ($q) {
                $q->productBranch();
            })
            ->when(! empty($this->request['name']), function ($q) {
                $q->where('products.name', 'like', '%' . $this->request['name'] . '%');
            })
            ->when(! empty($this->request['code']) || request()->filled('code') || request()->filled('search'), function ($q) {
                $code = ! empty($this->request['code']) ? $this->request['code'] : (request('code') ?: request('search'));
                $q->where('products.code', 'like', '%' . $code . '%');
            })
            ->when(! empty($this->request['category_id']), function ($q) {
                // Include all descendant categories
                $categoryIds = \Polirium\Modules\Product\Http\Model\Category::getAllDescendantIds((int)$this->request['category_id']);
                $q->whereIn('products.category_id', $categoryIds);
            })
            ->when(! empty($this->request['trademark_id']), function ($q) {
                $q->where('products.trademark_id', $this->request['trademark_id']);
            })
            ->when(! empty($this->request['shelve_id']), function ($q) {
                $q->where('products.shelve_id', $this->request['shelve_id']);
            })
            ->when(! empty($this->request['type']), function ($q) {
                $q->where('products.type', $this->request['type']);
            });
    }

    #[On('product-search-sidebar')]
    public function searchSidebar(mixed $value, string $key): void
    {
        $this->request[$key] = $value;
        $this->resetPage();
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
        })
        ->add('stock_formatted', function (Product $product) {
            // Nếu tồn kho >= 1e15 (gần INT_MAX) thì coi là "không giới hạn"
            if ($product->amount >= 1e15) {
                return '<span class="text-muted" title="Không giới hạn">∞</span>';
            }

            return core_number_format($product->amount);
        })
        ->add('type_name', function (Product $product) {
            return $product->type_name;
        })
        ->add('row_actions', function (Product $product) {
            return view('modules/product::index.datatable.actions', ['id' => $product->id, 'type' => (string) $product->type])->render();
        });
    }

    public function columns(): array
    {
        return [
            Column::add()
                ->title(__('modules/product::product.product_code_column'))
                ->field('code', 'products.code')
                ->sortable()
                ->searchable(),

            Column::add()
                ->title(__('modules/product::product.product_name_column'))
                ->field('name', 'products.name')
                ->sortable()
                ->searchable(),

            Column::add()
                ->title(__('modules/product::product.selling_price_column'))
                ->field('price_formatted', 'products.price')
                ->sortable()
                ->searchable(),

            ...(
                auth()->user()?->can('products.view-cost')
                ? [
                    Column::add()
                        ->title(__('modules/product::product.cost_price_column'))
                        ->field('cost_formatted', 'products.cost')
                        ->sortable()
                        ->searchable(),
                ]
                : []
            ),

            Column::add()
                ->title('ĐVT')
                ->field('unit', 'products.unit')
                ->sortable()
                ->searchable(),

            Column::add()
                ->title('Tồn kho')
                ->field('stock_formatted', 'stock_qty')
                ->sortable(),

            Column::add()
                ->title('Ghi chú')
                ->field('note', 'products.note')
                ->sortable()
                ->searchable(),

            // Column::make(__('modules/product::product.stock'), 'id')
            //         ->hidden(true, false)
            //         ->sortable()->searchable(), // tồn kho ở chi nhánh làm sau

            Column::make(__('modules/product::product.product_group_column'), 'category.name')
                    ->hidden(true, false)
                    ->sortable()->searchable(),

            Column::make(__('modules/product::product.trademark_column'), 'trademark.name')
                    ->hidden(true, false)
                    ->sortable()->searchable(),

            Column::make(__('modules/product::product.location_column'), 'shelve.name')
                    ->hidden(true, false)
                    ->sortable()->searchable(),

            Column::add()->title(__('modules/product::product.product_type_column'))
                    ->field('type_name', 'products.type')
                    ->hidden(true, false)
                    ->sortable()->searchable(),

            Column::add()
                ->title(__('modules/product::product.min_stock_level'))
                ->field('min_quantity', 'products.min_quantity')
                ->hidden(true, false)
                ->sortable()
                ->searchable(),

            Column::add()
                ->title(__('modules/product::product.max_stock_level'))
                ->field('max_quantity', 'products.max_quantity')
                ->hidden(true, false)
                ->sortable()
                ->searchable(),

            Column::make(trans('core/base::general.action'), 'row_actions')->bodyAttribute('text-center'),
        ];
    }

    public function filters(): array
    {
        return [
            // Filter::inputText('username')->operators(['contains']),
        ];
    }

}
