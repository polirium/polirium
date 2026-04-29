<?php

use Polirium\Modules\Customer\Http\Livewire\CustomerGroup\Datatable\CustomerGroupTable;
use Polirium\Modules\Customer\Http\Livewire\Index\Datatable\CustomerTable;
use Polirium\Modules\Customer\Http\Livewire\Index\FilterComponent;
use Polirium\Modules\Customer\Http\Livewire\Index\Modal\ModalCreateCustomerComponent;
use Polirium\Modules\Customer\Http\Livewire\Index\Modal\ModalCreateCustomerGroupComponent;
use Polirium\Modules\Customer\Http\Livewire\Index\Modal\ModalImportCustomerComponent;

return [
    'customer-table' => [
        'class' => CustomerTable::class,
        'alias' => 'modules/customer::customer-table',
        'description' => 'Customer Table',
    ],
    'customer-group-modal-create' => [
        'class' => ModalCreateCustomerGroupComponent::class,
        'alias' => 'modules/customer::index.modal.modal-create-customer-group',
        'description' => 'Modal create customer group',
    ],
    'customer-modal-create' => [
        'class' => ModalCreateCustomerComponent::class,
        'alias' => 'modules/customer::index.modal.modal-create-customer',
        'description' => 'Modal create customer',
    ],
    'filter-customer' => [
        'class' => FilterComponent::class,
        'alias' => 'modules/customer::index.filter',
        'description' => 'Filter customer',
    ],

    'customer-group-filter-sidebar' => [
        'class' => \Polirium\Modules\Customer\Http\Livewire\CustomerGroup\FilterSidebarComponent::class,
        'alias' => 'modules/customer::customer-group.filter-sidebar',
        'description' => 'Customer Group Filter Sidebar',
    ],

    'customer-group-table' => [
        'class' => CustomerGroupTable::class,
        'alias' => 'modules/customer::customer-group-table',
        'description' => 'Customer group Table',
    ],
    'customer-modal-import' => [
        'class' => ModalImportCustomerComponent::class,
        'alias' => 'modules/customer::index.modal.modal-import-customer',
        'description' => 'Modal import customers from Excel',
    ],
];
