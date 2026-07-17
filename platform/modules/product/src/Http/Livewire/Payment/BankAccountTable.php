<?php

namespace Polirium\Modules\Product\Http\Livewire\Payment;

use Illuminate\Database\Eloquent\Builder;
use Polirium\Core\Support\Http\Livewire\Tables\BaseTable;
use Polirium\Datatable\Button;
use Polirium\Datatable\Column;
use Polirium\Datatable\Facades\PowerGrid;
use Polirium\Datatable\PowerGridFields;
use Polirium\Modules\Product\Http\Model\Payment\BankAccount;
use Polirium\Modules\Product\Support\VietQrService;

final class BankAccountTable extends BaseTable
{
    public string $tableName = 'product-bank-account-table';

    public function setUp(): array
    {
        $this->showCheckBox();

        return [
            PowerGrid::header()->showSearchInput(),
            PowerGrid::footer()->showPerPage()->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return BankAccount::query();
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('name')
            ->add('account_number')
            ->add('bank_label', fn (BankAccount $model) => e(VietQrService::bankLabel($model->bank_code)))
            ->add('account_holder')
            ->add('is_active', function (BankAccount $model) {
                return $model->is_active
                    ? '<span class="badge bg-success-lt">Active</span>'
                    : '<span class="badge bg-danger-lt">Inactive</span>';
            })
            ->add('is_default', function (BankAccount $model) {
                return $model->is_default
                    ? '<span class="badge bg-primary-lt">Default</span>'
                    : '<span class="text-muted">-</span>';
            });
    }

    public function columns(): array
    {
        return [
            Column::make('ID', 'id')->sortable(),
            Column::make(__('Tên'), 'name')->searchable()->sortable(),
            Column::make(__('Số TK'), 'account_number')->searchable()->sortable(),
            Column::make(__('Ngân hàng'), 'bank_label', 'bank_code')->searchable()->sortable(),
            Column::make(__('Chủ TK'), 'account_holder')->searchable(),
            Column::make(__('Kích hoạt'), 'is_active'),
            Column::make(__('Mặc định'), 'is_default'),
            Column::action(trans('core/base::general.action')),
        ];
    }

    public function actions(BankAccount $row): array
    {
        return [
            Button::add('edit')
                ->slot(tabler_icon('pencil', ['class' => 'icon']))
                ->class('btn btn-outline-primary btn-icon btn-sm me-1')
                ->dispatch('modal-create-bank-account', ['id' => $row->id]),
            Button::add('delete')
                ->slot(tabler_icon('trash', ['class' => 'icon']))
                ->class('btn btn-outline-danger btn-icon btn-sm')
                ->dispatch('delete-bank-account', ['id' => $row->id])
                ->confirm(__('Bạn có chắc muốn xóa tài khoản này?')),
        ];
    }

    public function deleteBankAccount(int $id): void
    {
        abort_unless((int) auth()->id() === 1, 403);

        BankAccount::whereKey($id)->delete();
        $this->dispatch('pg:eventRefresh-product-bank-account-table');
    }

    protected function getListeners(): array
    {
        return array_merge(parent::getListeners(), [
            'delete-bank-account' => 'deleteBankAccount',
        ]);
    }
}
