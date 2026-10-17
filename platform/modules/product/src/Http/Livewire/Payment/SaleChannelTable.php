<?php

namespace Polirium\Modules\Product\Http\Livewire\Payment;

use Illuminate\Database\Eloquent\Builder;
use Polirium\Core\Support\Http\Livewire\Tables\BaseTable;
use Polirium\Datatable\ActionButtons;
use Polirium\Datatable\Column;
use Polirium\Datatable\Facades\PowerGrid;
use Polirium\Datatable\PowerGridFields;
use Polirium\Modules\Product\Http\Model\Payment\SaleChannel;

class SaleChannelTable extends BaseTable
{
    public string $tableName = 'sale-channel-table';

    protected function getListeners(): array
    {
        return array_merge(
            parent::getListeners(),
            [
                'refresh-datatable-sale-channel' => '$refresh',
                'sale-channel-created' => '$refresh',
            ]
        );
    }

    public function setUp(): array
    {
        $this->showCheckBox();

        return [
            PowerGrid::header()->showSearchInput()->showToggleColumns()->includeViewOnTop('modules/product::payment.sale-channel.datatable.header'),
            PowerGrid::footer()->showPerPage()->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return SaleChannel::query();
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('name')
            ->add('description')
            ->add('sort_order')
            ->add('is_active_formatted', function (SaleChannel $model) {
                return $model->is_active ? '<span class="badge bg-success-lt">Sử dụng</span>' : '<span class="badge bg-danger-lt">Không sử dụng</span>';
            })
            ->add('is_default_formatted', function (SaleChannel $model) {
                return $model->is_default ? '<span class="badge bg-primary-lt">Mặc định</span>' : '<span class="text-muted">-</span>';
            })
            ->add('created_at_formatted', function (SaleChannel $model) {
                return $model->created_at->format('d/m/Y H:i');
            });
    }

    public function columns(): array
    {
        return [
            Column::make('ID', 'id')
                ->sortable()
                ->searchable(),

            Column::make(trans('modules/product::product.channel_name'), 'name')
                ->sortable()
                ->searchable(),

            Column::make(trans('modules/product::product.description'), 'description')
                ->sortable()
                ->searchable(),

            Column::make(trans('modules/product::product.sort_order'), 'sort_order')
                ->sortable()
                ->editOnClick(hasPermission: auth()->user()?->can('products.sale-channel.edit') ?? false),

            Column::make(trans('Kích hoạt'), 'is_active_formatted', 'is_active'),

            Column::make('Mặc định', 'is_default_formatted', 'is_default'),

            Column::make(trans('modules/product::product.created_at'), 'created_at_formatted', 'created_at')
                ->sortable(),

            Column::action(trans('core/base::general.action')),
        ];
    }

    public function actions(SaleChannel $row): array
    {
        if (! auth()->user()->can('products.sale-channel.edit')) {
            return [];
        }

        return ActionButtons::make(
            ActionButtons::edit('show-modal-create-sale-channel', $row->id),
            ActionButtons::custom(
                'toggle-active',
                $row->is_active ? 'toggle-right' : 'toggle-left',
                $row->is_active ? trans('modules/product::product.deactivate') : __('Bật kích hoạt'),
            )
                ?->slot($row->is_active
                    ? tabler_icon('toggle-right', ['class' => 'icon text-success'])
                    : tabler_icon('toggle-left', ['class' => 'icon text-secondary']))
                ->dispatch('toggle-active-sale-channel', ['id' => $row->id]),
            ActionButtons::custom(
                'toggle-default',
                $row->is_default ? 'toggle-right' : 'toggle-left',
                'Đặt mặc định',
            )
                ?->slot($row->is_default
                    ? tabler_icon('toggle-right', ['class' => 'icon text-primary'])
                    : tabler_icon('toggle-left', ['class' => 'icon text-secondary']))
                ->dispatch('set-default-sale-channel', ['id' => $row->id]),
            ActionButtons::delete('trigger-delete', $row->id),
        );
    }

    #[\Livewire\Attributes\On('toggle-active-sale-channel')]
    public function toggleActive(int|string $id): void
    {
        $this->authorize('products.sale-channel.edit');

        $model = SaleChannel::find($id);
        if ($model) {
            $model->update(['is_active' => ! $model->is_active]);
        }
    }

    #[\Livewire\Attributes\On('set-default-sale-channel')]
    public function setDefault(int|string $id): void
    {
        $this->authorize('products.sale-channel.edit');

        SaleChannel::query()->update(['is_default' => false]);
        SaleChannel::where('id', $id)->update(['is_default' => true]);
    }

    #[\Livewire\Attributes\On('trigger-delete')]
    public function delete(int|string $id): void
    {
        $this->authorize('products.sale-channel.delete');

        SaleChannel::find($id)?->delete();
    }

    public function onUpdatedEditable(string|int $id, string $field, string $value): void
    {
        $this->authorize('products.sale-channel.edit');

        if ($field === 'sort_order') {
            SaleChannel::find($id)?->update(['sort_order' => (int) $value]);
        }
    }
}
