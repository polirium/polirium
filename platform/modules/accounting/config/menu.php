<?php

return [
    [
        'id' => 'module_accounting',
        'name' => 'modules/accounting::accounting.name',
        'route' => null,
        'icon' => 'category-filled',
        'sort' => 20,
    ],
    [
        'id' => 'module_accounting_index',
        'name' => 'modules/accounting::accounting.index',
        'route' => 'accountings.index',
        'parent' => 'module_accounting',
        'icon' => 'category-filled',
        'sort' => 3,
        'permission' => 'accountings.view',
    ],

    [
        'id' => 'module_accounting_invoice',
        'name' => 'modules/accounting::accounting.invoice.name',
        'route' => 'accountings.invoice',
        'parent' => 'module_vendor_trade',
        'icon' => 'category-filled',
        'sort' => 1,
        'permission' => 'accountings.invoices',
    ],

    [
        'id' => 'module_accounting_payment_refund',
        'name' => 'modules/accounting::accounting.payment_refund',
        'route' => 'accountings.payment.refund',
        'parent' => 'module_vendor_trade',
        'icon' => 'receipt-refund',
        'sort' => 2,
        'permission' => 'accountings.refunds',
    ],

    [
        'id' => 'module_accounting_payment_index',
        'name' => 'modules/accounting::accounting.sale_invoice_list',
        'route' => 'accountings.payment.index',
        'parent' => 'module_accounting',
        'icon' => 'cash',
        'sort' => 1,
        'permission' => 'accountings.payment',
    ],
    [
        'id' => 'module_accounting_report_sales',
        'name' => 'Báo cáo bán hàng',
        'route' => 'accountings.report.sales',
        'parent' => 'module_accounting',
        'icon' => 'report-analytics',
        'sort' => 2,
        'permission' => 'accountings.payment',
    ],
    [
        'id' => 'module_accounting_payment_methods',
        'name' => 'modules/accounting::accounting.payment_method',
        'route' => 'products.payment-methods.index',
        'parent' => 'module_accounting',
        'icon' => 'credit-card',
        'sort' => 4,
        // 'permission' => 'sales.payment.index', // Use appropriate permission
    ],
];
