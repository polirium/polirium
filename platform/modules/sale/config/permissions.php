<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Sales Management
    |--------------------------------------------------------------------------
    */
    [
        'name' => 'Bán hàng',
        'flag' => 'sales',
    ],

    /*
    |--------------------------------------------------------------------------
    | Quản lý Đơn Hàng (Sale Orders/Invoices)
    |--------------------------------------------------------------------------
    */
    [
        'name' => 'Danh sách đơn hàng',
        'flag' => 'sales.orders.index',
        'parent_flag' => 'sales',
    ],
    [
        'name' => 'Tạo đơn hàng',
        'flag' => 'sales.orders.create',
        'parent_flag' => 'sales',
    ],
    [
        'name' => 'Sửa đơn hàng',
        'flag' => 'sales.orders.edit',
        'parent_flag' => 'sales',
    ],
    [
        'name' => 'Xóa đơn hàng',
        'flag' => 'sales.orders.delete',
        'parent_flag' => 'sales',
    ],

    /*
    |--------------------------------------------------------------------------
    | Thanh Toán & Hóa Đơn (Payments)
    |--------------------------------------------------------------------------
    */
    [
        'name' => 'Thanh toán',
        'flag' => 'sales.payment.index',
        'parent_flag' => 'sales',
    ],
    [
        'name' => 'In hóa đơn',
        'flag' => 'sales.print',
        'parent_flag' => 'sales',
    ],
    [
        'name' => 'Hoàn tiền',
        'flag' => 'sales.payment.refund',
        'parent_flag' => 'sales',
    ],
    [
        'name' => 'Hủy hóa đơn',
        'flag' => 'sales.payment.cancel',
        'parent_flag' => 'sales',
    ],

    /*
    |--------------------------------------------------------------------------
    | Báo Cáo
    |--------------------------------------------------------------------------
    */
    [
        'name' => 'Báo cáo bán hàng',
        'flag' => 'sales.reports',
        'parent_flag' => 'sales',
    ],
];
