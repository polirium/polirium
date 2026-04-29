<?php

use Polirium\Modules\Product\Http\Livewire\Index\Datatable\ProductListTable;
use Polirium\Modules\Product\Http\Livewire\Index\Modal\ModalCreateCategoryComponent;
use Polirium\Modules\Product\Http\Livewire\Index\Modal\ModalCreateListComponent;
use Polirium\Modules\Product\Http\Livewire\Index\Modal\ModalCreateProductComponent;
use Polirium\Modules\Product\Http\Livewire\Payment\DraftPaymentListComponent;
use Polirium\Modules\Product\Http\Livewire\Payment\Modal\ModalCreatePartnerDeliveryComponent;
use Polirium\Modules\Product\Http\Livewire\Payment\Modal\ModalCreateSaleChannelComponent;
use Polirium\Modules\Product\Http\Livewire\Payment\Modal\ModalPaymentTypeComponent;
use Polirium\Modules\Product\Http\Livewire\Payment\PaymentComponent;
use Polirium\Modules\Product\Http\Livewire\Payment\TabPaymentComponent;
use Polirium\Modules\Product\Http\Livewire\Payment\TabPaymentV2Component;
use Polirium\Modules\Product\Http\Livewire\Refund\RefundComponent;
use Polirium\Modules\Product\Http\Livewire\Stock\Datatable\StockTable;
use Polirium\Modules\Product\Http\Livewire\Stock\Stock\StockComponent;

return [
    'product-list-table' => [
        'class' => ProductListTable::class,
        'alias' => 'modules/product::product-list-table',
        'description' => 'Product Table',
    ],
    'modal-create-product' => [
        'class' => ModalCreateProductComponent::class,
        'alias' => 'modules/product::index.modal.modal-create-product',
        'description' => 'Modal create product',
    ],
    'modal-create-category' => [
        'class' => ModalCreateCategoryComponent::class,
        'alias' => 'modules/product::index.modal.modal-create-category',
        'description' => 'Modal create category',
    ],
    'modal-create-list' => [
        'class' => ModalCreateListComponent::class,
        'alias' => 'modules/product::index.modal.modal-create-list',
        'description' => 'Modal create trademark and shelves',
    ],
    'modal-import-product' => [
        'class' => \Polirium\Modules\Product\Http\Livewire\Index\Modal\ModalImportProductComponent::class,
        'alias' => 'modules/product::index.modal.modal-import-product',
        'description' => 'Modal import products from Excel',
    ],
    'index.search-sidebar' => [
        'class' => \Polirium\Modules\Product\Http\Livewire\Index\SearchSidebarComponent::class,
        'alias' => 'modules/product::index.search-sidebar',
        'description' => 'Search sidebar for products',
    ],

    'product-tab-payment' => [
        'class' => TabPaymentComponent::class,
        'alias' => 'modules/product::payment.tab',
        'description' => 'Tab view payment',
    ],
    'product-payment' => [
        'class' => PaymentComponent::class,
        'alias' => 'modules/product::payment.payment',
        'description' => 'payment product',
    ],
    'product-payment-payment-type' => [
        'class' => ModalPaymentTypeComponent::class,
        'alias' => 'modules/product::payment.modal.modal-payment-type',
        'description' => 'Multiple payment methods',
    ],
    'product-payment-modal-create-sale-channel' => [
        'class' => ModalCreateSaleChannelComponent::class,
        'alias' => 'modules/product::payment.modal.modal-create-sale-channel',
        'description' => 'Create/Edit sale channel',
    ],
    'product-payment-modal-create-partner-delivery' => [
        'class' => ModalCreatePartnerDeliveryComponent::class,
        'alias' => 'modules/product::payment.modal.modal-create-partner-delivery',
        'description' => 'Create/Edit partner delivery',
    ],
    'product-price-setting-table' => [
        'class' => \Polirium\Modules\Product\Http\Livewire\PriceSetting\Datatable\PriceSettingTable::class,
        'alias' => 'modules/product::product-price-setting-table',
        'description' => 'Product Price Setting Table',
    ],
    'price-setting-filter-sidebar' => [
        'class' => \Polirium\Modules\Product\Http\Livewire\PriceSetting\FilterSidebarComponent::class,
        'alias' => 'modules/product::price-setting.filter-sidebar',
        'description' => 'Price Setting Filter Sidebar',
    ],
    'product-stock-table' => [
        'class' => StockTable::class,
        'alias' => 'modules/product::stock-table',
        'description' => 'Product Stock Table',
    ],
    'stock-filter-sidebar' => [
        'class' => \Polirium\Modules\Product\Http\Livewire\Stock\FilterSidebarComponent::class,
        'alias' => 'modules/product::stock.filter-sidebar',
        'description' => 'Stock Filter Sidebar',
    ],
    'product-stock-component' => [
        'class' => StockComponent::class,
        'alias' => 'modules/product::stock.stock.view',
        'description' => 'Product Stock View',
    ],

    'product-refund' => [
        'class' => RefundComponent::class,
        'alias' => 'modules/product::refund.view',
        'description' => 'Product Refund',
    ],
    'product-payment-v2' => [
        'class' => \Polirium\Modules\Product\Http\Livewire\Payment\PaymentV2Component::class,
        'alias' => 'modules/product::payment.payment-v2-component',
        'description' => 'payment product v2',
    ],
    'product-tab-payment-v2' => [
        'class' => TabPaymentV2Component::class,
        'alias' => 'modules/product::payment.tab-payment-v2-component',
        'description' => 'Tab view payment v2',
    ],
    'product-payment-modal-select-refund-invoice' => [
        'class' => \Polirium\Modules\Product\Http\Livewire\Payment\Modal\ModalSelectRefundInvoiceComponent::class,
        'alias' => 'modules/product::payment.modal.modal-select-refund-invoice',
        'description' => 'Modal select refund invoice',
    ],
    'product-refund-component' => [
        'class' => RefundComponent::class,
        'alias' => 'modules/product::refund.refund-component',
        'description' => 'Product Refund Component',
    ],

    // Dashboard Widgets
    'widget-revenue-stats' => [
        'class' => \Polirium\Modules\Product\Widgets\RevenueStatsWidget::class,
        'alias' => 'modules/product::widgets.revenue-stats',
        'description' => 'Revenue Statistics Widget',
    ],
    'widget-sales-summary' => [
        'class' => \Polirium\Modules\Product\Widgets\SalesSummaryWidget::class,
        'alias' => 'modules/product::widgets.sales-summary',
        'description' => 'Sales Summary Widget',
    ],
    'draft-payment-list' => [
        'class' => DraftPaymentListComponent::class,
        'alias' => 'modules/product::payment.draft-payment-list',
        'description' => 'Draft Payment List',
    ],
    'payment-method-table' => [
        'class' => \Polirium\Modules\Product\Http\Livewire\Payment\PaymentMethodTable::class,
        'alias' => 'modules/product::payment.payment-method-table',
        'description' => 'Payment Method Table',
    ],
    'modal-create-payment-method' => [
        'class' => \Polirium\Modules\Product\Http\Livewire\Payment\Modal\ModalCreatePaymentMethodComponent::class,
        'alias' => 'modules/product::payment.modal.modal-create-payment-method',
        'description' => 'Modal Create Payment Method',
    ],
    'sale-channel-table' => [
        'class' => \Polirium\Modules\Product\Http\Livewire\Payment\SaleChannelTable::class,
        'alias' => 'modules/product::payment.sale-channel-table',
        'description' => 'Sale Channel Table',
    ],
    'delivery-partner-table' => [
        'class' => \Polirium\Modules\Product\Http\Livewire\Payment\DeliveryPartnerTable::class,
        'alias' => 'modules/product::payment.delivery-partner-table',
        'description' => 'Delivery Partner Table',
    ],
    'modal-create-delivery-partner' => [
        'class' => \Polirium\Modules\Product\Http\Livewire\Payment\Modal\ModalCreateDeliveryPartnerComponent::class,
        'alias' => 'modules/product::payment.modal.modal-create-delivery-partner',
        'description' => 'Modal create delivery partner',
    ],
];
