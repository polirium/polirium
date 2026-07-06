<?php

namespace Polirium\Modules\Vendor\Http\Livewire\Refund\Datatable;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Polirium\Core\Support\Http\Livewire\Tables\BaseTable;
use Polirium\Datatable\Button;
use Polirium\Datatable\Column;
use Polirium\Datatable\Components\SetUp\Exportable;
use Polirium\Datatable\Facades\PowerGrid;
use Polirium\Datatable\PowerGridFields;
use Polirium\Modules\Product\Http\Model\ProductLog;
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
        ->orderByDesc('id');
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

    public function actions(Refund $row): array
    {
        $actions = [];

        if (auth()->user()?->can('vendors.refunds.edit')) {
            $actions[] = Button::add('edit-refund')
                ->slot(tabler_icon('pencil', ['class' => 'icon']))
                ->id()
                ->class('btn btn-primary btn-icon btn-sm me-1')
                ->attributes([
                    'aria-label' => __('modules/vendor::purchase.edit'),
                    'title' => __('modules/vendor::purchase.edit'),
                ])
                ->tooltip(__('modules/vendor::purchase.edit'))
                ->route('vendors.purchases.refund', ['id' => $row->id]);
        }

        if (($row->status ?? '') !== 'cancel' && auth()->user()?->can('vendors.refunds.edit')) {
            $actions[] = Button::add('cancel-refund')
                ->slot(tabler_icon('circle-x', ['class' => 'icon']))
                ->id()
                ->class('btn btn-warning btn-icon btn-sm me-1')
                ->attributes([
                    'aria-label' => __('Hủy phiếu trả hàng nhập'),
                    'title' => __('Hủy phiếu trả hàng nhập'),
                ])
                ->tooltip(__('Hủy phiếu trả hàng nhập'))
                ->confirm(__('Bạn có chắc chắn muốn hủy phiếu trả hàng nhập này? Tồn kho và công nợ liên quan sẽ được hoàn lại.'))
                ->dispatch('cancelRefund', ['id' => $row->id]);
        }

        if (auth()->user()?->can('vendors.refunds.delete')) {
            $actions[] = Button::add('delete-refund')
                ->slot(tabler_icon('trash', ['class' => 'icon']))
                ->id()
                ->class('btn btn-outline-danger btn-icon btn-sm')
                ->attributes([
                    'aria-label' => __('Xóa phiếu trả hàng nhập'),
                    'title' => __('Xóa phiếu trả hàng nhập'),
                ])
                ->tooltip(__('Xóa phiếu trả hàng nhập'))
                ->confirm(__('Bạn có chắc chắn muốn xóa phiếu trả hàng nhập này? Thao tác này không thể hoàn tác.'))
                ->dispatch('deleteRefund', ['id' => $row->id]);
        }

        return $actions;
    }

    #[On('redirect-purchase-refund-view')]
    public function redirectPurchaseView()
    {
        return redirect(route('vendors.purchases.refund'));
    }

    #[On('cancelRefund')]
    public function cancel(int|string $id): void
    {
        $this->authorize('vendors.refunds.edit');

        $refund = Refund::with(['products', 'purchase'])->find($id);

        if (! $refund) {
            $this->dispatch('error', 'Không tìm thấy phiếu trả hàng nhập.');

            return;
        }

        if ($refund->status === 'cancel') {
            $this->dispatch('error', 'Phiếu trả hàng nhập đã bị hủy trước đó.');

            return;
        }

        DB::transaction(function () use ($refund) {
            $this->revertCompletedRefund($refund);

            $refund->update(['status' => 'cancel']);
            $this->restorePurchaseStatus($refund);
        });

        $this->dispatch('success', 'Đã hủy phiếu trả hàng nhập và hoàn lại tồn kho.');
        $this->dispatch('pg:eventRefresh-' . $this->tableName);
    }

    #[On('deleteRefund')]
    public function delete(int|string $id): void
    {
        $this->authorize('vendors.refunds.delete');

        $refund = Refund::with(['products', 'purchase'])->find($id);

        if (! $refund) {
            $this->dispatch('error', 'Không tìm thấy phiếu trả hàng nhập.');

            return;
        }

        DB::transaction(function () use ($refund) {
            $this->revertCompletedRefund($refund);

            $refund->products()->delete();
            $refund->delete();
            $this->restorePurchaseStatus($refund);
        });

        $this->dispatch('success', 'Đã xóa phiếu trả hàng nhập và hoàn lại tồn kho.');
        $this->dispatch('pg:eventRefresh-' . $this->tableName);
    }

    private function revertCompletedRefund(Refund $refund): void
    {
        if (! in_array($refund->status, ['success', 'completed', 'paid'], true)) {
            return;
        }

        $logs = ProductLog::query()
            ->where('productable_type', Refund::class)
            ->where('productable_id', $refund->id)
            ->get();

        if ($logs->isNotEmpty()) {
            foreach ($logs as $log) {
                $delta = abs((int) $log->amount_after - (int) $log->amount_before);

                if ($delta <= 0) {
                    continue;
                }

                change_product_amount(
                    (int) $log->product_id,
                    $delta,
                    $log->direction === 'out',
                    $log->branch_id ?: $refund->branch_id
                );
            }
        } else {
            foreach ($refund->products as $item) {
                change_product_amount(
                    $item->product_id,
                    $item->amount,
                    true,
                    $refund->branch_id
                );
            }
        }

        ProductLog::where('productable_type', Refund::class)
            ->where('productable_id', $refund->id)
            ->delete();

        if ($refund->vendor_id && $refund->purchase?->status === 'refund') {
            $vendor = Vendor::find($refund->vendor_id);

            if ($vendor) {
                $vendor->increment('debt', (float) $refund->value);
                $vendor->increment('total', (float) $refund->value);
            }
        }
    }

    private function restorePurchaseStatus(Refund $refund): void
    {
        $purchase = $refund->purchase;

        if (! $purchase || $purchase->status !== 'refund') {
            return;
        }

        $hasOtherSuccessfulRefund = Refund::where('purchase_id', $purchase->id)
            ->where('id', '!=', $refund->id)
            ->whereIn('status', ['success', 'completed', 'paid'])
            ->exists();

        if (! $hasOtherSuccessfulRefund) {
            $purchase->update(['status' => 'success']);
        }
    }
}
