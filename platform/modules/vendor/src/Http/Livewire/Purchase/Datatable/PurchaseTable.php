<?php

namespace Polirium\Modules\Vendor\Http\Livewire\Purchase\Datatable;

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
use Polirium\Modules\Vendor\Http\Model\Vendor;

final class PurchaseTable extends BaseTable
{
    public string $tableName = 'table-purchases';

    public $tab = 1;

    public array $sidebarFilters = [
        'code' => '',
        'status' => '',
        'date' => '',
    ];

    protected function getListeners(): array
    {
        return array_merge(
            parent::getListeners(),
            [
                'datatable-purchase-filter' => 'applyFilter',
                'datatable-purchase-filter-clear' => 'clearSidebarFilter',
            ]
        );
    }

    public function applyFilter($value, $key): void
    {
        $this->sidebarFilters[$key] = $value;
    }

    public function clearSidebarFilter(): void
    {
        $this->sidebarFilters = ['code' => '', 'status' => '', 'date' => ''];
    }

    public function setUp(): array
    {
        $this->showCheckBox();

        return [
            PowerGrid::exportable('export')->striped()->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV),

            PowerGrid::header()->showSearchInput()->showToggleColumns()->includeViewOnTop('modules/vendor::purchase.datatable.header'),
            PowerGrid::footer()->showPerPage()->showRecordCount(),
            PowerGrid::detail()->showCollapseIcon()->collapseOthers()->view('modules/vendor::purchase.index.datatable.detail'),
        ];
    }

    public function header(): array
    {
        return [];
    }

    public function datasource(): Builder
    {
        return Purchase::query()
            ->with(['vendor', 'branch', 'userCreated', 'products.product', 'refunds'])
            ->withCount('products')
            ->withSum('products as total_value_sum', 'value')
            ->when(user_branch(), function ($q) {
                $q->where('branch_id', user_branch());
            })
            ->when(! empty($this->sidebarFilters['code']), function ($q) {
                $q->where('code', 'like', '%' . $this->sidebarFilters['code'] . '%');
            })
            ->when(! empty($this->sidebarFilters['status']), function ($q) {
                $q->where('status', $this->sidebarFilters['status']);
            })
            ->when(! empty($this->sidebarFilters['date']), function ($q) {
                $dates = explode(' to ', $this->sidebarFilters['date']);
                if (count($dates) === 2) {
                    $q->whereDate('created_at', '>=', $dates[0])
                        ->whereDate('created_at', '<=', $dates[1]);
                } elseif (count($dates) === 1 && ! empty($dates[0])) {
                    $q->whereDate('created_at', $dates[0]);
                }
            })
            ->orderByDesc("id");
    }

    public function relationSearch(): array
    {
        return [
            'vendor' => ['name'],
            'branch' => ['name'],
            'userCreated' => ['name'],
        ];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('code')
            ->add('created_at')
            ->add('vendor_name', fn(Purchase $model) => $model->vendor?->name ?? '-')
            ->add('branch_name', fn(Purchase $model) => $model->branch?->name ?? '-')
            ->add('user_created_name', fn(Purchase $model) => $model->userCreated?->name ?? '-')
            ->add('total_value', fn(Purchase $model) => auth()->user()?->can('vendors.purchases.view-price') ? core_number_format($model->total_value_sum ?? $model->total ?? 0) : '***')
            ->add('products_count')
            ->add('products', fn(Purchase $model) => $model->products)
            ->add('payment_formatted', fn(Purchase $model) => auth()->user()?->can('vendors.purchases.view-price') ? core_number_format($model->value ?? 0) : '***')
            ->add('need_pay_formatted', fn(Purchase $model) => auth()->user()?->can('vendors.purchases.view-price') ? core_number_format($model->need_pay ?? 0) : '***')
            ->add('status')
            ->add('status_name', fn(Purchase $model) => match($model->status ?? 'temp') {
                'completed', 'paid' => __('modules/vendor::purchase.status.success'),
                'pending', 'temp' => __('modules/vendor::purchase.status.temp'),
                'cancelled' => 'Đã hủy',
                default => $model->status ?? 'temp'
            })
            ->add('refund_id', fn(Purchase $model) => $model->refunds->last()?->id)
            ->add('total')
            ->add('note')
            ->add('value')
            ->add('need_pay');
    }

    public function columns(): array
    {
        return [
            Column::make(trans('core/base::general.id'), 'id')->sortable()->searchable(),
            Column::make(trans('modules/vendor::purchase.code'), 'code')->sortable()->searchable(),
            Column::make(trans('modules/vendor::purchase.created_at'), 'created_at')->sortable()->searchable(),
            Column::make(trans('modules/vendor::vendor.name'), 'vendor_name')->sortable()->searchable(),
            Column::make(trans('Chi nhánh'), 'branch_name')->sortable()->searchable(),
            ...(auth()->user()?->can('vendors.purchases.view-price') ? [
                Column::make(trans('modules/vendor::purchase.total_value'), 'total_value')->sortable(),
            ] : []),
            Column::make(trans('modules/vendor::purchase.total_amount'), 'products_count')->sortable(),
            ...(auth()->user()?->can('vendors.purchases.view-price') ? [
                Column::make(trans('modules/vendor::purchase.payment'), 'payment_formatted')->sortable(),
                Column::make(trans('modules/vendor::purchase.need_pay'), 'need_pay_formatted')->sortable(),
            ] : []),
            Column::make(trans('core/base::general.status'), 'status')->sortable()->searchable(),
            Column::action(trans('core/base::general.action')),
        ];
    }

    public function filters(): array
    {
        return [
            // Filter::inputText('username')->operators(['contains']),
        ];
    }

    public function actions(Purchase $row): array
    {
        return [];
    }

    #[On('redirect-purchase-view')]
    public function redirectPurchaseView()
    {
        return redirect(route('vendors.purchases.order'));
    }

    /**
     * Hủy phiếu nhập: chuyển status → cancelled, revert tồn kho nhưng GIỮ record.
     */
    public function cancel($id): void
    {
        $this->authorize('vendors.purchases.edit');

        if (! auth()->user()?->can('vendors.purchases.edit')) {
            $this->dispatch('error', 'Bạn không có quyền hủy phiếu nhập.');

            return;
        }

        $purchase = Purchase::with('products')->where('id', $id)->first();

        if (! $purchase) {
            $this->dispatch('error', 'Không tìm thấy phiếu nhập.');

            return;
        }

        if ($purchase->status === 'cancelled') {
            $this->dispatch('error', 'Phiếu nhập đã bị hủy trước đó.');

            return;
        }

        // If status is success/completed, revert stock and vendor stats
        if (in_array($purchase->status, ['success', 'completed'])) {
            // Delete associated ProductLogs to clean up "Thẻ kho"
            \Polirium\Modules\Product\Http\Model\ProductLog::where('productable_type', Purchase::class)
                ->where('productable_id', $purchase->id)
                ->delete();

            foreach ($purchase->products as $item) {
                change_product_amount(
                    $item->product_id,
                    $item->amount,
                    false, // decrease (revert addition)
                    $purchase->branch_id
                );
            }

            // Revert vendor stats
            if ($purchase->vendor_id) {
                $vendor = Vendor::find($purchase->vendor_id);
                if ($vendor) {
                    $vendor->decrement('debt', ((float) $purchase->need_pay - (float) $purchase->value));
                    $vendor->decrement('total', $purchase->need_pay);
                    $vendor->decrement('total_purchase', $purchase->need_pay);
                }
            }
        }

        // Chuyển status sang cancelled (giữ record + products)
        $purchase->update(['status' => 'cancelled']);

        $this->dispatch('success', 'Đã hủy phiếu nhập hàng và cập nhật tồn kho.');
    }

    /**
     * Xóa phiếu nhập hoàn toàn (chỉ dành cho admin / quyền delete).
     */
    public function delete($id)
    {
        if (! auth()->user()?->can('vendors.purchases.delete')) {
            $this->dispatch('error', 'Bạn không có quyền xóa phiếu nhập.');

            return;
        }

        $purchase = Purchase::with('products')->where('id', $id)->first();

        if (! $purchase) {
            $this->dispatch('error', 'Không tìm thấy phiếu nhập.');

            return;
        }

        // If status is success, we need to revert the stock and vendor stats
        if (in_array($purchase->status, ['success', 'completed'])) {
            // Delete associated ProductLogs first to clean up "Thẻ kho"
            \Polirium\Modules\Product\Http\Model\ProductLog::where('productable_type', Purchase::class)
                ->where('productable_id', $purchase->id)
                ->delete();

            foreach ($purchase->products as $item) {
                // Revert stock quantity directly without creating a new log
                change_product_amount(
                    $item->product_id,
                    $item->amount,
                    false, // decrease (revert addition)
                    $purchase->branch_id
                );
            }

            // Revert vendor stats
            if ($purchase->vendor_id) {
                $vendor = Vendor::find($purchase->vendor_id);
                if ($vendor) {
                    $vendor->decrement('debt', ((float) $purchase->need_pay - (float) $purchase->value));
                    $vendor->decrement('total', $purchase->need_pay);
                    $vendor->decrement('total_purchase', $purchase->need_pay);
                }
            }
        }

        // Delete products
        $purchase->products()->delete();

        // Delete purchase
        $purchase->delete();

        $this->dispatch('success', 'Đã xóa phiếu nhập hàng và cập nhật tồn kho.');
    }

    public string $bulkDeletePermission = 'vendors.purchases.delete';
}
