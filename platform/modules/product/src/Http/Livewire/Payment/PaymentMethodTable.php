<?php

namespace Polirium\Modules\Product\Http\Livewire\Payment;

use Illuminate\Database\Eloquent\Builder;
use Polirium\Core\Support\Http\Livewire\Tables\BaseTable;
use Polirium\Datatable\Button;
use Polirium\Datatable\Column;
use Polirium\Datatable\Facades\PowerGrid;
use Polirium\Datatable\PowerGridFields;
use Polirium\Modules\Product\Http\Model\Payment\PaymentMethod;

final class PaymentMethodTable extends BaseTable
{
    public string $tableName = 'product-payment-method-table';

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
        return PaymentMethod::query();
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('name')
            ->add('code')
            ->add('description')
            ->add('sort_order')
            ->add('is_active', function (PaymentMethod $model) {
                return $model->is_active ? '<span class="badge bg-success-lt">Active</span>' : '<span class="badge bg-danger-lt">Inactive</span>';
            })
            ->add('is_default', function (PaymentMethod $model) {
                return $model->is_default ? '<span class="badge bg-primary-lt">Yes</span>' : '<span class="badge bg-secondary-lt">No</span>';
            })
            ->add('target_payment_status', function (PaymentMethod $model) {
                return match($model->target_payment_status) {
                    'completed' => '<span class="badge bg-success-lt">Completed</span>',
                    'pending' => '<span class="badge bg-warning-lt">Pending (COD/Debt)</span>',
                    default => '<span class="badge bg-secondary-lt">' . $model->target_payment_status . '</span>',
                };
            })
            ->add('created_at_formatted', function (PaymentMethod $model) {
                return $model->created_at->format('d/m/Y H:i');
            });
    }

    public function columns(): array
    {
        return [
            Column::make('ID', 'id')->sortable(),
            Column::make('Name', 'name')->searchable()->sortable(),
            Column::make('Code', 'code')->searchable()->sortable(),
            Column::make('Description', 'description')->searchable(),
            Column::make('Active', 'is_active'),
            Column::make('Default', 'is_default'),
            Column::make('Target Status', 'target_payment_status', 'target_payment_status')->sortable(),
            Column::make(trans('modules/product::product.sort_order'), 'sort_order')->sortable()->editOnClick(hasPermission: auth()->user()?->can('products.payment-method.edit') ?? false),
            Column::make('Created At', 'created_at_formatted', 'created_at')->sortable(),
            Column::action(trans('core/base::general.action')),
        ];
    }

    public function actions(PaymentMethod $row): array
    {
        $actions = [];

        if (auth()->user()?->can('products.payment-method.edit')) {
            $actions[] = Button::add('edit')
                ->slot(tabler_icon('pencil', ['class' => 'icon']))
                ->id()
                ->class('btn btn-primary btn-icon btn-sm me-1')
                ->attributes([
                    'aria-label' => __('Sửa'),
                    'title' => __('Sửa'),
                ])
                ->dispatch('modal-create-payment-method', ['id' => $row->id]);

            $actions[] = Button::add('delete')
                ->slot(tabler_icon('trash', ['class' => 'icon']))
                ->id()
                ->class('btn btn-outline-danger btn-icon btn-sm')
                ->attributes([
                    'aria-label' => __('Xóa'),
                    'title' => __('Xóa'),
                    'wire:confirm' => __('Bạn có chắc chắn muốn xóa phương thức thanh toán này?'),
                ])
                ->dispatch('delete-payment-method', ['id' => $row->id]);
        }

        return $actions;
    }

    #[\Livewire\Attributes\On('delete-payment-method')]
    public function delete(int|string $id): void
    {
        $this->authorize('products.payment-method.delete');
        PaymentMethod::find($id)?->delete();
    }

    public function onUpdatedEditable(string|int $id, string $field, string $value): void
    {
        $this->authorize('products.payment-method.edit');
        if ($field === 'sort_order') {
            PaymentMethod::find($id)?->update(['sort_order' => (int) $value]);
        }
    }
}
