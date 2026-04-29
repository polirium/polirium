<?php

namespace Polirium\Modules\Product\Http\Livewire\Stock\Stock;

use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use Polirium\Modules\Product\Http\Model\Product;
use Polirium\Modules\Product\Http\Model\Stock\Stock;
use Polirium\Modules\Product\Imports\StockImport;

class StockComponent extends Component
{
    use WithFileUploads;
    public ?int $stock_id = 0;

    public bool $viewMode = false;

    public ?string $stockDateTime = null;

    #[Validate([
        'stock.code' => ['required', 'string', 'max:255'],
        'stock.branch_id' => ['required', 'integer', 'exists:branches,id'],
        'stock.amount' => ['nullable', 'integer', 'min:0'],
        'stock.increase_deviation' => ['nullable', 'integer', 'min:0'],
        'stock.decrease_deviation' => ['nullable', 'integer', 'min:0'],
        'stock.deviation' => ['nullable', 'integer'],
        'stock.value' => ['nullable', 'integer'],
        'stock.user_id' => ['nullable', 'integer', 'exists:users,id'],
        'stock.user_created_id' => ['required', 'integer', 'exists:users,id'],
        'stock.status' => ['required', 'string', 'in:draft,completed,pending,approved,cancelled'],
        'stock.note' => ['nullable', 'string', 'max:1000'],
    ])]
    public Stock $stock;

    #[Validate([
        'products' => ['required', 'array', 'min:1'],
        'products.*.product' => ['required'],
        'products.*.amount' => ['required', 'integer', 'min:0'],
        'products.*.actual_stock' => ['required', 'integer', 'min:0'],
        'products.*.quantity_difference' => ['nullable', 'integer'],
        'products.*.value' => ['required', 'numeric', 'min:0'],
        'products.*.value_difference' => ['nullable', 'numeric'],
        'products.*.note' => ['nullable', 'string', 'max:255'],
    ])]
    public array $products = [];

    public string $filter = 'all';

    public ?string $search = null;

    public $stockCode;

    public $importFile;

    // Lifecycle Hooks
    public function mount($id = null, bool $viewMode = false)
    {
        $this->viewMode = $viewMode;

        if ($id) {
            $this->stock_id = (int) $id;
        }

        if ($this->stock_id) {
            $this->stock = Stock::with('products')->findOrFail($this->stock_id);
            $this->stockCode = $this->stock->code;
            $this->stockDateTime = $this->stock->created_at?->format('Y-m-d\TH:i');

            // Nếu phiếu đã completed → tự động chuyển sang viewMode
            if ($this->stock->status === 'completed') {
                $this->viewMode = true;
            }

            foreach ($this->stock->products as $product) {
                $this->products[$product->product_id] = [
                    'product' => $product->product,
                    'amount' => $product->amount,
                    'actual_stock' => $product->actual_stock ?? 1,
                    'quantity_difference' => $product->quantity_difference ?? 0,
                    'value' => $product->value,
                    'value_difference' => $product->value_difference ?? 0,
                    'note' => $product->note,
                ];
            }
        } else {
            $this->stock = new Stock();
            $this->stockCode = code_generate('KK', Stock::withTrashed()->max('id') ?? 0);
            $this->stock->code = $this->stockCode;
            $this->stock->branch_id = user_branch();
            $this->stock->amount = 0;
            $this->stock->increase_deviation = 0;
            $this->stock->decrease_deviation = 0;
            $this->stock->deviation = 0;
            $this->stock->value = 0;
            $this->stock->user_id = auth()->id();
            $this->stock->user_created_id = auth()->id();
            $this->stock->status = 'draft';
        }
    }

    public function updatedProducts($value, $key)
    {
        // Khi actual_stock thay đổi, tự động tính lại differences
        if (str_contains($key, '.actual_stock')) {
            $product_id = explode('.', $key)[0];
            $this->calculateDifferences($product_id);
        }

        // Cập nhật summary sau khi có thay đổi
        $this->updateStockSummary();
    }

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }

    // Computed Properties
    #[Computed]
    public function searchProducts()
    {
        if (empty($this->search) || strlen($this->search) < 0) {
            return collect();
        }

        return Product::where(function ($query) {
            $query->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('code', 'like', '%' . $this->search . '%');
        })
        ->limit(10)
        ->get();
    }

    #[Computed]
    public function totalActualQuantity()
    {
        return collect($this->products)->sum('actual_stock');
    }

    #[Computed]
    public function filteredProducts()
    {
        if ($this->filter === 'all') {
            return $this->products;
        }

        return collect($this->products)->filter(function ($item) {
            return $this->isProductMatchingStatus($item, $this->filter);
        })->toArray();
    }

    #[Computed]
    public function matchedCount()
    {
        return $this->getProductCountByStatus('matched');
    }

    #[Computed]
    public function mismatchedCount()
    {
        return $this->getProductCountByStatus('mismatched');
    }

    public function render()
    {
        return view('modules/product::stock.stock.view');
    }

    /**
     * Import sản phẩm từ file Excel (2 cột: Mã hàng, Số lượng thực tế).
     */
    public function importFromExcel(): void
    {
        if ($this->viewMode) {
            return;
        }

        $this->validate([
            'importFile' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        $import = new StockImport();
        Excel::import($import, $this->importFile->getRealPath());

        // Merge imported products vào danh sách hiện tại
        foreach ($import->importedProducts as $productId => $productData) {
            $this->products[$productId] = $productData;
            $this->calculateDifferences($productId);
        }

        $this->updateStockSummary();

        $importedCount = count($import->importedProducts);
        $errorCount = count($import->errors);

        $message = "Đã nhập {$importedCount} sản phẩm từ Excel.";
        if ($errorCount > 0) {
            $message .= " ({$errorCount} lỗi: " . implode('; ', array_slice($import->errors, 0, 3)) . ')';
        }

        session()->flash($errorCount > 0 ? 'warning' : 'success', $message);

        $this->reset('importFile');
    }

    /**
     * Tải file mẫu Excel kiểm kho.
     */
    public function downloadImportTemplate()
    {
        $headers = ['Mã hàng', 'Số lượng thực tế'];
        $callback = function () use ($headers) {
            $file = fopen('php://output', 'w');
            // UTF-8 BOM
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, $headers);
            fputcsv($file, ['VD_001', '10']);
            fputcsv($file, ['VD_002', '25']);
            fclose($file);
        };

        return response()->streamDownload($callback, 'Mau_Kiem_Kho.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    // Regular Methods
    public function selectProduct($product_id)
    {
        if ($this->viewMode) {
            return;
        }

        if (isset($this->products[$product_id])) {
            $this->products[$product_id]['actual_stock']++;
            $this->calculateDifferences($product_id);
        } else {
            $product = Product::find($product_id);
            $this->products[$product_id] = [
                'product' => $product,
                'amount' => $product->amount ?? 0,
                'actual_stock' => 1,
                'quantity_difference' => 0,
                'value' => $product->cost ?? 0,
                'value_difference' => 0,
                'note' => '',
            ];
            $this->calculateDifferences($product_id);
        }

        // Cập nhật summary sau khi thêm/sửa sản phẩm
        $this->updateStockSummary();
    }

    public function removeProduct($product_id)
    {
        // Block removal on completed stock checks for users without delete permission
        if (($this->stock->status ?? '') === 'completed') {
            $user = auth()->user();
            if (! $user?->hasRole('admin') && ! $user?->can('products.stock.delete')) {
                session()->flash('error', trans('modules/product::product.cannot_delete_completed'));

                return;
            }
        }

        unset($this->products[$product_id]);

        // Cập nhật summary sau khi xoá sản phẩm
        $this->updateStockSummary();
    }

    public function calculateDifferences($product_id)
    {
        if (isset($this->products[$product_id])) {
            $item = &$this->products[$product_id];
            $branchStock = $item['amount'] ?? 0;
            $actualStock = $item['actual_stock'] ?? 1;
            $cost = $item['product']['cost'] ?? 0;

            $item['quantity_difference'] = $actualStock - $branchStock;
            $item['value_difference'] = $item['quantity_difference'] * $cost;
        }
    }

    private function getProductCountByStatus(string $status): int
    {
        return collect($this->products)->filter(function ($item) use ($status) {
            return $this->isProductMatchingStatus($item, $status);
        })->count();
    }

    private function isProductMatchingStatus(array $item, string $status): bool
    {
        $branchStock = $item['amount'] ?? 0;
        $actualStock = $item['actual_stock'] ?? 1;

        return match($status) {
            'matched' => $branchStock == $actualStock,
            'mismatched' => $branchStock != $actualStock,
            default => false
        };
    }

    /**
     * Lưu ghi chú phiếu kiểm kho (chỉ cho phép khi viewMode = completed)
     */
    public function saveNote(): void
    {
        if (! $this->stock_id || $this->stock->status !== 'completed') {
            return;
        }

        $this->validateOnly('stock.note');
        $this->stock->save();

        session()->flash('success', trans('modules/product::stock.note_updated'));
    }

    /**
     * Hủy phiếu kiểm kho: chuyển status → cancelled, revert product logs.
     */
    public function cancelStock()
    {
        if (! $this->stock_id) {
            return;
        }

        if ($this->stock->status === 'cancelled') {
            session()->flash('error', 'Phiếu kiểm kho đã bị hủy trước đó.');

            return;
        }

        // Revert product logs nếu phiếu đã completed
        if ($this->stock->status === 'completed') {
            \Polirium\Modules\Product\Http\Model\ProductLog::where('productable_type', Stock::class)
                ->where('productable_id', $this->stock->id)
                ->delete();

            // Revert số lượng sản phẩm
            foreach ($this->products as $productId => $productData) {
                $quantityDifference = $productData['quantity_difference'] ?? 0;
                if ($quantityDifference != 0) {
                    change_product_amount(
                        $productId,
                        abs($quantityDifference),
                        $quantityDifference < 0, // reverse: nếu tăng thì giảm lại, nếu giảm thì tăng lại
                        $this->stock->branch_id
                    );
                }
            }
        }

        $this->stock->update(['status' => 'cancelled']);

        session()->flash('success', 'Đã hủy phiếu kiểm kho.');

        return $this->redirect(route('products.stock.index'));
    }

    /**
     * Admin: Cập nhật thời gian phiếu kiểm kho.
     */
    public function updateStockDateTime(): void
    {
        if (! auth()->user()?->hasRole('admin')) {
            session()->flash('error', 'Chỉ admin mới có quyền chỉnh giờ phiếu kiểm kho.');

            return;
        }

        if (! $this->stock_id || empty($this->stockDateTime)) {
            return;
        }

        $this->stock->update(['created_at' => $this->stockDateTime]);

        session()->flash('success', 'Đã cập nhật thời gian phiếu kiểm kho.');
    }

    public function save(string $status = 'draft')
    {
        $this->authorize($this->stock_id ? 'products.stock.manage' : 'products.stock.create');

        // Nếu viewMode, chỉ cho lưu ghi chú
        if ($this->viewMode) {
            $this->saveNote();

            return;
        }

        // Calculate summary data before saving
        $this->updateStockSummary();

        // Ensure code is sync
        $this->stock->code = $this->stockCode;

        // Ensure code is generated if missing
        if (empty($this->stock->code)) {
            $this->stockCode = code_generate('KK', Stock::withTrashed()->max('id'));
            $this->stock->code = $this->stockCode;
        }
        if (empty($this->stock->branch_id)) {
            $this->stock->branch_id = user_branch();
        }
        if (empty($this->stock->user_created_id)) {
            $this->stock->user_created_id = auth()->id();
        }
        if (empty($this->stock->user_id)) {
            $this->stock->user_id = auth()->id();
        }

        // Set status before validation
        $this->stock->status = $status;

        // Validate all data with custom rules
        $this->validate();

        // Save stock record
        $this->stock->save();

        // Save stock products
        $this->stock->products()->forceDelete();
        foreach ($this->products as $productId => $productData) {
            $product = $productData['product'];
            unset($productData['product']);

            $productData['product_id'] = $product['id'];

            $this->stock->products()->create($productData);

            // Log product stock changes
            $quantityDifference = $productData['quantity_difference'] ?? 0;

            if ($this->stock->status === 'completed') {
                product_logs(
                    $productId,
                    $this->stock->id,
                    Stock::class,
                    abs($quantityDifference),
                    $product['cost'] ?? 0,
                    $productData['value_difference'] ?? 0,
                    $quantityDifference > 0 // true if increase, false if decrease
                );
            }
        }

        // Show success message
        session()->flash('success', trans('modules/product::stock.saved_successfully'));

        // Redirect to stock list or stay on current page
        return redirect()->route('products.stock.index');
    }

    private function updateStockSummary(): void
    {
        $products = collect($this->products);

        // Tổng số lượng thực tế
        $this->stock->amount = $products->sum('actual_stock');

        // Tổng giá trị chênh lệch
        $this->stock->value = $products->sum('value_difference');

        // Tính toán độ lệch
        $increaseDeviation = $products->filter(function ($item) {
            return ($item['quantity_difference'] ?? 0) > 0;
        })->sum('quantity_difference');

        $decreaseDeviation = $products->filter(function ($item) {
            return ($item['quantity_difference'] ?? 0) < 0;
        })->sum('quantity_difference');

        $this->stock->increase_deviation = $increaseDeviation;
        $this->stock->decrease_deviation = abs($decreaseDeviation);
        $this->stock->deviation = $increaseDeviation + $decreaseDeviation;
    }
}
