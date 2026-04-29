<?php

use Polirium\Modules\Accounting\Http\Livewire\Index\Datatable\AccountingTable;
use Polirium\Modules\Accounting\Http\Livewire\Index\Modal\ModalCreateAccountingComponent;
use Polirium\Modules\Accounting\Http\Livewire\Index\Modal\ModalCreateAccountingPayPersonComponent;
use Polirium\Modules\Accounting\Http\Livewire\Index\Modal\ModalCreateAccountingTypeComponent;
use Polirium\Modules\Accounting\Http\Livewire\Invoice\Datatable\InvoiceTable;
use Polirium\Modules\Accounting\Http\Livewire\Payment\Datatable\PaymentTable;
use Polirium\Modules\Accounting\Http\Livewire\Payment\Modal\ModalCreateSaleInvoiceComponent;
use Polirium\Modules\Accounting\Http\Livewire\Payment\Refund\Datatable\PaymentRefundTable;
use Polirium\Modules\Accounting\Http\Livewire\Payment\Refund\Detail\PaymentRefundDetail;

return [
    'accounting-table' => [
        'class' => AccountingTable::class,
        'alias' => 'modules/accounting::accounting-table',
        'description' => 'Accounting Table',
    ],
    'modal-create-accounting' => [
        'class' => ModalCreateAccountingComponent::class,
        'alias' => 'modules/accounting::index.modal.modal-create-accounting',
        'description' => 'Modal Create accounting',
    ],
    'modal-create-accounting-type' => [
        'class' => ModalCreateAccountingTypeComponent::class,
        'alias' => 'modules/accounting::index.modal.modal-create-accounting-type',
        'description' => 'Modal Create accounting type',
    ],
    'modal-create-accounting-pay-person' => [
        'class' => ModalCreateAccountingPayPersonComponent::class,
        'alias' => 'modules/accounting::index.modal.modal-create-accounting-pay-person',
        'description' => 'Modal Create accounting pay person',
    ],
    'accounting-index-search-sidebar' => [
        'class' => \Polirium\Modules\Accounting\Http\Livewire\Index\SearchSidebarComponent::class,
        'alias' => 'modules/accounting::index.search-sidebar',
        'description' => 'Search sidebar for accounting',
    ],

    'accounting-invoice-table' => [
        'class' => InvoiceTable::class,
        'alias' => 'modules/accounting::invoice-table',
        'description' => 'Accounting invoice payment table',
    ],
    'accounting-invoice-search-sidebar' => [
        'class' => \Polirium\Modules\Accounting\Http\Livewire\Invoice\SearchSidebarComponent::class,
        'alias' => 'modules/accounting::invoice.search-sidebar',
        'description' => 'Search sidebar for invoice',
    ],

    'payment-refund-table' => [
        'class' => PaymentRefundTable::class,
        'alias' => 'modules/accounting::payment.refund.datatable.payment-refund-table',
        'description' => 'Payment Refund Table',
    ],

    'payment-refund-detail' => [
        'class' => PaymentRefundDetail::class,
        'alias' => 'modules/accounting::payment.refund.detail.payment-refund-detail',
        'description' => 'Payment Refund Detail',
    ],

    'payment-table' => [
        'class' => PaymentTable::class,
        'alias' => 'modules/accounting::payment.datatable.payment-table',
        'description' => 'Payment Table - Hóa đơn bán hàng',
    ],

    // Dashboard Widgets
    'widget-invoice-stats' => [
        'class' => \Polirium\Modules\Accounting\Widgets\InvoiceStatsWidget::class,
        'alias' => 'modules/accounting::widgets.invoice-stats',
        'description' => 'Invoice Statistics Widget',
    ],

    // Filter Sidebars
    'payment-filter-sidebar' => [
        'class' => \Polirium\Modules\Accounting\Http\Livewire\Payment\FilterSidebarComponent::class,
        'alias' => 'modules/accounting::payment.filter-sidebar',
        'description' => 'Payment Filter Sidebar',
    ],
    'payment-refund-filter-sidebar' => [
        'class' => \Polirium\Modules\Accounting\Http\Livewire\Payment\Refund\FilterSidebarComponent::class,
        'alias' => 'modules/accounting::payment.refund.filter-sidebar',
        'description' => 'Payment Refund Filter Sidebar',
    ],

    // Payment Modal Components
    'modal-create-sale-invoice' => [
        'class' => ModalCreateSaleInvoiceComponent::class,
        'alias' => 'modules/accounting::payment.modal.modal-create-sale-invoice',
        'description' => 'Modal Create Sale Invoice - Quick create sale invoice',
    ],
    'modal-quick-update' => [
        'class' => \Polirium\Modules\Accounting\Http\Livewire\Payment\Modal\ModalQuickUpdateComponent::class,
        'alias' => 'modules/accounting::payment.modal.modal-quick-update-component',
        'description' => 'Modal Quick Update Payment Info',
    ],
    'payment-note' => [
        'class' => \Polirium\Modules\Accounting\Http\Livewire\Payment\PaymentNote::class,
        'alias' => 'modules/accounting::payment.payment-note',
        'description' => 'Payment Note Component',
    ],
    'payment-actions' => [
        'class' => \Polirium\Modules\Accounting\Http\Livewire\Payment\PaymentActions::class,
        'alias' => 'modules/accounting::payment.payment-actions',
        'description' => 'Payment Actions Component',
    ],
    'refund-component' => [
        'class' => \Polirium\Modules\Accounting\Http\Livewire\Payment\RefundComponent::class,
        'alias' => 'modules/accounting::payment.refund-component',
        'description' => 'Refund Component',
    ],
    'accounting-dashboard-component' => [
        'class' => \Polirium\Modules\Accounting\Http\Livewire\Dashboard\AccountingDashboardComponent::class,
        'alias' => 'modules/accounting::dashboard.accounting-dashboard-component',
        'description' => 'Accounting Dashboard Widgets',
    ],

    // Report Components
    'sales-report' => [
        'class' => \Polirium\Modules\Accounting\Http\Livewire\Report\SalesReportComponent::class,
        'alias' => 'modules/accounting::report.sales-report',
        'description' => 'Sales Report Component',
    ],
];
