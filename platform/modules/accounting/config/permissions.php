<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Quản lý Kế toán
    |--------------------------------------------------------------------------
    */
    [
        'name' => 'Kế toán',
        'flag' => 'accountings',
    ],
    [
        'name' => 'Xem sổ quỹ',
        'flag' => 'accountings.index',
        'parent_flag' => 'accountings',
    ],
    [
        'name' => 'Tạo phiếu thu/chi',
        'flag' => 'accountings.create',
        'parent_flag' => 'accountings',
    ],
    [
        'name' => 'Xem hoá đơn',
        'flag' => 'accountings.invoices',
        'parent_flag' => 'accountings',
    ],
    [
        'name' => 'Phiếu thanh toán',
        'flag' => 'accountings.payments',
        'parent_flag' => 'accountings',
    ],
    [
        'name' => 'Hoàn tiền',
        'flag' => 'accountings.refunds',
        'parent_flag' => 'accountings',
    ],
    [
        'name' => 'Hủy hóa đơn',
        'flag' => 'accountings.cancel',
        'parent_flag' => 'accountings',
    ],
    [
        'name' => 'Chỉnh sửa hóa đơn',
        'flag' => 'accountings.edit',
        'parent_flag' => 'accountings',
    ],
    [
        'name' => 'Xem báo cáo tổng quan',
        'flag' => 'accountings.dashboard',
        'parent_flag' => 'accountings',
    ],
    [
        'name' => 'Xem tổng doanh thu',
        'flag' => 'accountings.dashboard.revenue',
        'parent_flag' => 'accountings.dashboard',
    ],
    [
        'name' => 'Xem giảm giá',
        'flag' => 'accountings.dashboard.discount',
        'parent_flag' => 'accountings.dashboard',
    ],
    [
        'name' => 'Xem khách cần trả',
        'flag' => 'accountings.dashboard.payable',
        'parent_flag' => 'accountings.dashboard',
    ],
    [
        'name' => 'Xem khách đã trả',
        'flag' => 'accountings.dashboard.paid',
        'parent_flag' => 'accountings.dashboard',
    ],
    [
        'name' => 'Xem còn cần thu (Công nợ)',
        'flag' => 'accountings.dashboard.debt',
        'parent_flag' => 'accountings.dashboard',
    ],
    [
        'name' => 'Xem phương thức thanh toán',
        'flag' => 'accountings.dashboard.payment_methods',
        'parent_flag' => 'accountings.dashboard',
    ],
    [
        'name' => 'Xem kênh bán hàng',
        'flag' => 'accountings.dashboard.sale_channels',
        'parent_flag' => 'accountings.dashboard',
    ],
    [
        'name' => 'Xem đối tác giao hàng',
        'flag' => 'accountings.dashboard.delivery_partners',
        'parent_flag' => 'accountings.dashboard',
    ],
    [
        'name' => 'Xem giá vốn',
        'flag' => 'accountings.dashboard.cogs',
        'parent_flag' => 'accountings.dashboard',
    ],

    /*
    |--------------------------------------------------------------------------
    | Xuất Excel hóa đơn
    |--------------------------------------------------------------------------
    */
    [
        'name' => 'Xuất Excel hóa đơn',
        'flag' => 'accountings.export',
        'parent_flag' => 'accountings',
    ],
];
