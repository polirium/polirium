<?php

namespace Polirium\Modules\Accounting\Http\Livewire\Invoice\Datatable;

use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use Polirium\Core\Support\Http\Livewire\Tables\BaseTable;
use Polirium\Datatable\Column;
use Polirium\Datatable\Components\SetUp\Exportable;
use Polirium\Datatable\Facades\PowerGrid;
use Polirium\Datatable\PowerGridFields;
use Polirium\Modules\Accounting\Http\Model\Payment;

final class InvoiceTable extends BaseTable
{
    public string $tableName = 'table-invoice';

    public $editingNoteId = null;
    public $noteContent = '';

    public array $request = [];

    public int $tab = 1;

    public function setUp(): array
    {
        $this->showCheckBox();

        return [
            PowerGrid::exportable('export')->striped()->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV),

            PowerGrid::header()->showSearchInput()->showToggleColumns(),
            PowerGrid::footer()->showPerPage()->showRecordCount(),
            PowerGrid::detail()->showCollapseIcon()->collapseOthers()->view('modules/accounting::invoice.datatable.detail'),
        ];
    }

    public function header(): array
    {
        return [];
    }

    public function datasource(): Builder
    {
        return Payment::query()
            ->with(['branch', 'user', 'finance', 'products.product'])
            ->when(user_branch(), function ($q) {
                $q->where('branch_id', user_branch()); // lấy theo chi nhánh đăng nhập
            })
            ->when(! empty($this->request['code']), function ($q) {
                $q->where('code', 'like', '%' . $this->request['code'] . '%');
            })
            ->when(! empty($this->request['customer_name']), function ($q) {
                $q->whereHas('finance.customer', function ($query) {
                    $query->where('name', 'like', '%' . $this->request['customer_name'] . '%');
                });
            })
            ->when(! empty($this->request['status']), function ($q) {
                $q->where('status', $this->request['status']);
            })
            ->when(! empty($this->request['date_from']), function ($q) {
                $q->whereDate('created_at', '>=', $this->request['date_from']);
            })
            ->when(! empty($this->request['date_to']), function ($q) {
                $q->whereDate('created_at', '<=', $this->request['date_to']);
            })
            ->when(! empty($this->request['value_min']), function ($q) {
                $q->where('value', '>=', $this->request['value_min']);
            })
            ->when(! empty($this->request['value_max']), function ($q) {
                $q->where('value', '<=', $this->request['value_max']);
            })
            ->orderByDesc('id');
    }

    #[On('invoice-search-sidebar')]
    public function searchSidebar(mixed $value, string $key): void
    {
        $this->request[$key] = $value;
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
        ->add('id')
        ->add('code')
        ->add('created_at_format', function (Payment $payment) {
            return core_format_date($payment->created_at);
        })
        ->add('customer_name_format', function (Payment $payment) {
            return $payment->finance?->customer?->name ?? $payment->customer?->name ?? '-';
        })
        ->add('total_format', function (Payment $payment) {
            return core_number_format($payment->total);
        })
        ->add('pay_format', function (Payment $payment) {
            $numb = '';
            if ($payment->discount_value) {
                $numb = core_number_format($payment->discount_value) . $payment->discount_type;
            }

            // return $numb . core_number_format($payment->pay);
            return $numb;
        })
        ->add('value_format', function (Payment $payment) {
            return core_number_format($payment->value);
        })
        ->add('products', fn (Payment $model) => $model->products->toArray())
        ->add('finance', fn (Payment $model) => $model->finance?->toArray())
        ->add('branch_name', fn (Payment $model) => $model->branch?->name ?? '-')
        ->add('user_name', fn (Payment $model) => $model->user?->name ?? '-')
        ->add('note')
        ->add('status')
        ->add('status_name')
        ->add('date')
        ->add('value');
    }

    public function columns(): array
    {
        return [
            Column::make(trans('core/base::general.id'), 'id')->sortable()->searchable(),
            Column::make(trans('modules/accounting::accounting.code'), 'code')->searchable()->sortable(),
            Column::make(trans('modules/accounting::accounting.time'), 'created_at_format')->searchable()->sortable(),
            Column::make(trans('modules/accounting::accounting.customer'), 'customer_name_format')->searchable()->sortable(),
            Column::make(trans('modules/accounting::accounting.total_goods'), 'total_format')->searchable()->sortable(),
            Column::make(trans('modules/accounting::accounting.payment_amount'), 'pay_format')->searchable()->sortable(),
            Column::make(trans('modules/accounting::accounting.customer_paid'), 'value_format')->searchable()->sortable(),
            Column::make(trans('modules/accounting::accounting.status'), 'status_name')->searchable()->sortable(),
            Column::action(trans('core/base::general.action')),
        ];
    }

    public function filters(): array
    {
        return [
            // Filter::inputText('username')->operators(['contains']),
        ];
    }

    public function actions($row): array
    {
        return [];
    }

    public function cancel($id)
    {
        if (! auth()->user()->can('accountings.cancel')) {
            $this->dispatch('error', 'Bạn không có quyền hủy hóa đơn.');

            return;
        }

        $payment = Payment::with('products')->find($id);

        if (! $payment) {
            $this->dispatch('error', 'Không tìm thấy hóa đơn.');

            return;
        }

        if (in_array($payment->status, ['cancelled', 'failed'])) {
            $this->dispatch('error', 'Hóa đơn đã bị hủy trước đó.');

            return;
        }

        // Revert Stock (Add back to stock)
        foreach ($payment->products as $item) {
            change_product_amount(
                $item->product_id,
                $item->amount,
                true, // increase (revert sale)
                $payment->branch_id
            );
        }

        // Update Status
        $payment->status = 'cancelled';
        $payment->save();

        if ($payment->finance) {
            $payment->finance->status = 'cancelled';
            $payment->finance->save();
        }

        $this->dispatch('success', 'Đã hủy hóa đơn và hoàn lại tồn kho.');
    }

    public function editNote($id, $note)
    {
        $this->editingNoteId = $id;
        $this->noteContent = $note;
    }

    public function saveNote()
    {
        $this->authorize('accountings.edit');

        if ($this->editingNoteId) {
            $payment = Payment::find($this->editingNoteId);
            if ($payment) {
                $payment->note = $this->noteContent;
                $payment->save();
                $this->dispatch('success', 'Đã cập nhật ghi chú.');
            }
            $this->editingNoteId = null;
            $this->noteContent = '';
        }
    }

    public function cancelEdit()
    {
        $this->editingNoteId = null;
        $this->noteContent = '';
    }
}
