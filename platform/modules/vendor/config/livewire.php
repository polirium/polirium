<?php

use Polirium\Modules\Vendor\Http\Livewire\Index\Modal\ModalCreateVendorComponent;
use Polirium\Modules\Vendor\Http\Livewire\Index\Datatable\VendorTable;
use Polirium\Modules\Vendor\Http\Livewire\Index\Modal\ModalCreateVendorGroupComponent;
use Polirium\Modules\Vendor\Http\Livewire\Index\SearchSidebarComponent;
use Polirium\Modules\Vendor\Http\Livewire\Purchase\Datatable\PurchaseTable;
use Polirium\Modules\Vendor\Http\Livewire\Purchase\OrderComponent;
use Polirium\Modules\Vendor\Http\Livewire\Purchase\RefundComponent;
use Polirium\Modules\Vendor\Http\Livewire\Refund\Datatable\RefundTable;
use Polirium\Modules\Vendor\Http\Livewire\Transfer\Datatable\TransferTable;
use Polirium\Modules\Vendor\Http\Livewire\Transfer\TransferComponent;

return [
    'search-sidebar-vendor' => [
        'class' => SearchSidebarComponent::class,
        'alias' => 'modules/vendor::index.search-sidebar',
        'description' => 'Vendor searech sidebar',
    ],
    'vendor-table' => [
        'class' => VendorTable::class,
        'alias' => 'modules/vendor::vendor-table',
        'description' => 'Vendor Table',
    ],
    'modal-create-vendor' => [
        'class' => ModalCreateVendorComponent::class,
        'alias' => 'modules/vendor::index.modal.modal-create-vendor',
        'description' => 'Modal create vendor',
    ],
    'modal-create-vendor-group' => [
        'class' => ModalCreateVendorGroupComponent::class,
        'alias' => 'modules/vendor::index.modal.modal-create-vendor-group',
        'description' => 'Modal create vendor group',
    ],
    'vendor-group-table' => [
        'class' => \Polirium\Modules\Vendor\Http\Livewire\VendorGroup\Datatable\VendorGroupTable::class,
        'alias' => 'modules/vendor::vendor-group-table',
        'description' => 'Vendor Group Table',
    ],

    'vendor-group-filter-sidebar' => [
        'class' => \Polirium\Modules\Vendor\Http\Livewire\VendorGroup\FilterSidebarComponent::class,
        'alias' => 'modules/vendor::vendor-group.filter-sidebar',
        'description' => 'Vendor Group Filter Sidebar',
    ],

    'purchase-table' => [
        'class' => PurchaseTable::class,
        'alias' => 'modules/vendor::purchase-table',
        'description' => 'Purchase Table',
    ],
    'purchase-filter-sidebar' => [
        'class' => \Polirium\Modules\Vendor\Http\Livewire\Purchase\FilterSidebarComponent::class,
        'alias' => 'modules/vendor::purchase.filter-sidebar',
        'description' => 'Purchase Filter Sidebar',
    ],
    'purchase-order' => [
        'class' => OrderComponent::class,
        'alias' => 'modules/vendor::purchase.order.view',
        'description' => 'Purchase order',
    ],
    'purchase-refund' => [
        'class' => RefundComponent::class,
        'alias' => 'modules/vendor::purchase.refund.view',
        'description' => 'Purchase refund',
    ],

    'purchase-refund-table' => [
        'class' => RefundTable::class,
        'alias' => 'modules/vendor::purchase-refund-table',
        'description' => 'Purchase refund table',
    ],
    'purchase-refund-filter-sidebar' => [
        'class' => \Polirium\Modules\Vendor\Http\Livewire\Purchase\Refund\FilterSidebarComponent::class,
        'alias' => 'modules/vendor::purchase.refund.filter-sidebar',
        'description' => 'Purchase Refund Filter Sidebar',
    ],

    'modal-import-purchase' => [
        'class' => \Polirium\Modules\Vendor\Http\Livewire\Purchase\Modal\ModalImportPurchaseComponent::class,
        'alias' => 'modules/vendor::purchase.modal.modal-import-purchase',
        'description' => 'Modal import purchase products from Excel',
    ],
    'modal-import-refund' => [
        'class' => \Polirium\Modules\Vendor\Http\Livewire\Purchase\Modal\ModalImportRefundComponent::class,
        'alias' => 'modules/vendor::purchase.modal.modal-import-refund',
        'description' => 'Modal import refund products from Excel',
    ],

    'transfers-table' => [
        'class' => TransferTable::class,
        'alias' => 'modules/vendor::transfer-table',
        'description' => 'Transfer Table',
    ],
    'transfer-filter-sidebar' => [
        'class' => \Polirium\Modules\Vendor\Http\Livewire\Transfer\FilterSidebarComponent::class,
        'alias' => 'modules/vendor::transfer.filter-sidebar',
        'description' => 'Transfer Filter Sidebar',
    ],
    'transfer-transfer' => [
        'class' => TransferComponent::class,
        'alias' => 'modules/vendor::transfer.transfer.view',
        'description' => 'Transfer products',
    ],
];
