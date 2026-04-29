<?php

namespace Polirium\Modules\Customer\Http\Livewire\Index\Datatable;

use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Polirium\Core\Support\Http\Livewire\Tables\BaseTable;
use Polirium\Core\UI\Facades\Assets;
use Polirium\Datatable\Button;
use Polirium\Datatable\Column;
use Polirium\Datatable\Components\SetUp\Exportable;
use Polirium\Datatable\Facades\PowerGrid;
use Polirium\Datatable\PowerGridFields;
use Polirium\Modules\Customer\Http\Model\Customer;

final class CustomerTable extends BaseTable
{
    public string $tableName = 'table-customers';

    #[Url(as: 'show_detail')]
    public $showDetailId = null;

    public $tab = 1;

    public $search_customer = [];

    public function mount(): void
    {
        parent::mount();
        Assets::loadCss('professional-table');
    }

    protected function getListeners(): array
    {
        return array_merge(
            parent::getListeners(),
            [
                'refresh-datatable-customers' => '$refresh',
            ]
        );
    }

    public function setUp(): array
    {
        $this->showCheckBox();

        return [
            PowerGrid::exportable('export')->striped()->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV),

            PowerGrid::header()->showSearchInput()->showToggleColumns()->includeViewOnTop('modules/customer::index.datatable.header'),
            PowerGrid::footer()->showPerPage()->showRecordCount(),

            PowerGrid::detail()->showCollapseIcon()->collapseOthers()->view('modules/customer::index.datatable.detail'),
        ];
    }

    public function datasource(): Builder
    {
        return Customer::query()
        ->with([
            'customerGroups',
            'user:id,name',
            'branch:id,name',
        ])
        ->when(isset_value($this->search_customer['customer_group']), function ($q) {
            $q->whereRelation('customerGroups', 'customer_group_id', $this->search_customer['customer_group']);
        })
        ->when($this->showDetailId, function ($q) {
            $q->where('id', $this->showDetailId);
        })
        ->orderByDesc('id');
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('birthday_formatted', function ($row) {
                return core_format_date($row->birthday);
            })
            ->add('created_at_formatted', function ($row) {
                return core_format_date($row->created_at);
            })
            ->add('customer_groups_formatted', function ($row) {
                return $row->customerGroups->pluck('name')->join(', ');
            })
        ;
    }

    public function columns(): array
    {
        return [
            Column::make(__('modules/customer::customer.code_column'), 'code')
                ->sortable()
                ->searchable(),

            Column::make(__('modules/customer::customer.name_column'), 'name')
                ->sortable()
                ->searchable(),

            Column::make(__('modules/customer::customer.type_column'), 'type')
                ->sortable()
                ->searchable()
                ->hidden(true, false),

            Column::make(__('modules/customer::customer.phone_column'), 'phone')
                ->sortable()
                ->searchable(),

            Column::make(__('modules/customer::customer.customer_groups_column'), 'customer_groups_formatted'),

            Column::make(__('modules/customer::customer.gender_column'), 'gender')
                ->sortable()
                ->hidden(true, false),

            Column::add()->title(__('modules/customer::customer.birthday_column'))->field('birthday_formatted', 'birthday')
                ->sortable()
                ->hidden(true, false),

            Column::make(__('modules/customer::customer.email_column'), 'email')
                ->sortable()
                ->searchable()
                ->hidden(true, false),

            Column::make(__('modules/customer::customer.facebook_column'), 'facebook')
                ->sortable()
                ->searchable()
                ->hidden(true, false),

            Column::make(__('modules/customer::customer.company_column'), 'company')
                ->sortable()
                ->searchable()
                ->hidden(true, false),

            Column::make(__('modules/customer::customer.vat_column'), 'vat')
                ->sortable()
                ->searchable()
                ->hidden(true, false),

            Column::make(__('modules/customer::customer.address_column'), 'address')
                ->sortable()
                ->searchable()
                ->hidden(true, false),

            Column::make(__('modules/customer::customer.province_district_ward_column'), 'province_district_ward')
                ->sortable()
                ->hidden(true, false),

            Column::make(__('modules/customer::customer.created_by_column'), 'user.name')
                ->sortable()
                ->hidden(true, false),

            Column::add()->title(__('modules/customer::customer.created_at_column'))->field('created_at_formatted', 'created_at')
                ->sortable()
                ->hidden(true, false),

            Column::make(__('modules/customer::customer.note_column'), 'note')
                ->sortable()
                ->searchable()
                ->hidden(true, false),

            Column::make(__('modules/customer::customer.branch_column'), 'branch.name')
                ->sortable()
                ->hidden(true, false),

            Column::action(trans('core/base::general.action')),
        ];
    }

    // public function filters(): array
    // {
    //     return [
    //         Filter::inputText('username')->operators(['contains']),
    //     ];
    // }

    #[\Livewire\Attributes\On('edit')]
    public function edit($rowId): void
    {
        $this->js('alert(' . $rowId . ')');
    }

    public function actions(Customer $row): array
    {
        $actions = [];

        if (auth()->user()->can('customers.edit')) {
            $actions[] = Button::add('edit')
                ->slot(tabler_icon('pencil', ['class' => 'icon']))
                ->id()
                ->class('btn btn-primary btn-icon btn-sm me-1')
                ->attributes([
                    'aria-label' => __('modules/customer::customer.edit'),
                    'title' => __('modules/customer::customer.edit'),
                ])
                ->tooltip(__('modules/customer::customer.edit'))
                ->dispatch('show-modal-create-customer', ['id' => $row->id]);
        }

        if (auth()->user()->can('customers.destroy')) {
            $actions[] = Button::add('delete')
                ->slot(tabler_icon('trash', ['class' => 'icon']))
                ->id()
                ->class('btn btn-outline-danger btn-icon btn-sm')
                ->attributes([
                    'aria-label' => __('modules/customer::customer.delete'),
                    'title' => __('modules/customer::customer.delete'),
                    'data-bs-toggle' => 'modal',
                    'data-bs-target' => '#modal-confirm-delete-customer',
                    'onclick' => "window.dispatchEvent(new CustomEvent('set-delete-customer-id', {detail: {id: {$row->id}}}))",
                ])
                ->tooltip(__('modules/customer::customer.delete'));
        }

        return $actions;
    }

    #[\Livewire\Attributes\On('datatable-customer-filter')]
    public function customerFilters($value, $key)
    {
        $this->search_customer[$key] = $value;
    }

    #[\Livewire\Attributes\On('triggerRemoveCustomer')]
    public function triggerRemoveCustomer($id): void
    {
        if (! auth()->user()->can('customers.destroy')) {
            $this->dispatch('error', trans('modules/customer::customer.no_permission'));

            return;
        }

        try {
            $customer = Customer::findOrFail($id);
            $customer->delete();
            $this->dispatch('success', trans('modules/customer::customer.deleted_success'));
            $this->dispatch('pg:eventRefresh-CustomerTable');
        } catch (\Exception $e) {
            $this->dispatch('error', trans('modules/customer::customer.cannot_delete'));
        }
    }

    /*
    public function actionRules($row): array
    {
       return [
            // Hide button edit for ID 1
            Rule::button('edit')
                ->when(fn($row) => $row->id === 1)
                ->hide(),
        ];
    }
    */
    public string $bulkDeletePermission = 'customers.destroy';
}
