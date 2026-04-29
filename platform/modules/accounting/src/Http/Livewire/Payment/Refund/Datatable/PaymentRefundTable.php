<?php

namespace Polirium\Modules\Accounting\Http\Livewire\Payment\Refund\Datatable;

use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Polirium\Core\Support\Http\Livewire\Tables\BaseTable;
use Polirium\Datatable\Column;

use Polirium\Datatable\Components\SetUp\Exportable;
use Polirium\Datatable\Facades\PowerGrid;
use Polirium\Datatable\PowerGridFields;
use Polirium\Modules\Product\Http\Model\Refund\Refund;

final class PaymentRefundTable extends BaseTable
{
    public string $tableName = 'table-payment-refund';

    #[Url]
    public string $search = '';

    public function setUp(): array
    {
        $this->showCheckBox();

        return [
            PowerGrid::exportable('export')->striped()->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV),

            PowerGrid::header()
                ->showSearchInput()
                ->showToggleColumns()
                ->includeViewOnTop('modules/accounting::payment.refund.datatable.header'),
            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount(),
            PowerGrid::detail()
                ->showCollapseIcon()
                ->collapseOthers()
                ->view('modules/accounting::payment.refund.datatable.detail'),
        ];
    }

    public string $bulkDeletePermission = 'accountings.refunds';

    public function delete(string|int $id): void
    {
        $item = Refund::find($id);
        $item?->delete();
    }
    public function header(): array
    {
        return [];
    }

    public function datasource(): Builder
    {
        return Refund::query()
            ->with(['payment.customer', 'creator', 'user', 'products.product'])
            ->when(user_branch(), function ($q) {
                $q->whereHas('payment', function ($query) {
                    $query->where('branch_id', user_branch());
                });
            })
            ->orderByDesc('id');
    }

    public function relationSearch(): array
    {
        return [
            'payment' => [
                'code',
            ],
            'payment.customer' => [
                'name',
                'phone',
            ],
            'creator' => [
                'name',
            ],
        ];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('code')
            ->add('payment_code', fn ($model) => $model->payment?->code ?? 'N/A')
            ->add('customer_name', fn ($model) => $model->payment?->customer?->name ?? 'Khách lẻ')
            ->add('customer_phone', fn ($model) => $model->payment?->customer?->phone ?? '')
            ->add('amount')
            ->add('original_total_amount_formatted', fn ($model) => core_number_format(($model->original_total_amount ?? 0) / 100))
            ->add('value_formatted', fn ($model) => core_number_format(($model->value ?? 0) / 100))
            ->add('creator_name', fn ($model) => $model->creator?->name ?? '')
            ->add('created_at_formatted', fn ($model) => $model->created_at?->format('d/m/Y H:i'))
            ->add('note');
    }

    public function columns(): array
    {
        return [
            Column::make('ID', 'id')
                ->sortable()
                ->searchable(),

            Column::make('Mã refund', 'code')
                ->sortable()
                ->searchable(),

            Column::make('Mã đơn hàng', 'payment_code')
                ->sortable(),

            Column::make('Khách hàng', 'customer_name')
                ->sortable(),

            Column::make('SĐT', 'customer_phone'),

            Column::make('SL SP', 'amount')
                ->sortable(),

            Column::make('Tổng tiền gốc', 'original_total_amount_formatted')
                ->sortable(),

            Column::make('Tổng tiền trả', 'value_formatted')
                ->sortable(),

            Column::make('Người tạo', 'creator_name'),

            Column::make('Ngày tạo', 'created_at_formatted')
                ->sortable(),

            Column::make('Ghi chú', 'note'),

            Column::action(trans('core/base::general.action')),
        ];
    }

    public function actions($row): array
    {
        return [
            // PowerGrid detail đã thay thế button detail
        ];
    }

    #[On('pg:eventRefresh-{tableName}')]
    public function refresh(): void
    {
        $this->fillData();
    }
}
