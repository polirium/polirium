<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Quản lý Sản phẩm
    |--------------------------------------------------------------------------
    */
    [
        'name' => 'Sản phẩm',
        'flag' => 'products',
    ],
    [
        'name' => 'Xem danh sách sản phẩm',
        'flag' => 'products.index',
        'parent_flag' => 'products',
    ],
    [
        'name' => 'Tạo sản phẩm',
        'flag' => 'products.create',
        'parent_flag' => 'products',
    ],
    [
        'name' => 'Sửa sản phẩm',
        'flag' => 'products.edit',
        'parent_flag' => 'products',
    ],
    [
        'name' => 'Xoá sản phẩm',
        'flag' => 'products.destroy',
        'parent_flag' => 'products',
    ],

    /*
    |--------------------------------------------------------------------------
    | Thiết lập giá
    |--------------------------------------------------------------------------
    */
    [
        'name' => 'Thiết lập giá',
        'flag' => 'products.price-setting',
        'parent_flag' => 'products',
    ],

    /*
    |--------------------------------------------------------------------------
    | Quản lý Kho
    |--------------------------------------------------------------------------
    */
    [
        'name' => 'Kiểm kho',
        'flag' => 'products.stock.index',
        'parent_flag' => 'products',
    ],
    [
        'name' => 'Tạo phiếu kiểm kho',
        'flag' => 'products.stock.create',
        'parent_flag' => 'products',
    ],
    [
        'name' => 'Xem phiếu kiểm kho',
        'flag' => 'products.stock.view',
        'parent_flag' => 'products',
    ],
    [
        'name' => 'Xóa phiếu kiểm kho',
        'flag' => 'products.stock.delete',
        'parent_flag' => 'products',
    ],
    [
        'name' => 'Nhập/Xuất kho',
        'flag' => 'products.stock.manage',
        'parent_flag' => 'products',
    ],

    /*
    |--------------------------------------------------------------------------
    | Kênh bán hàng & Đối tác vận chuyển
    |--------------------------------------------------------------------------
    */
    [
        'name' => 'Kênh bán hàng',
        'flag' => 'products.sale-channel',
        'parent_flag' => 'products',
    ],
    [
        'name' => 'Xem kênh bán hàng',
        'flag' => 'products.sale-channel.index',
        'parent_flag' => 'products',
    ],
    [
        'name' => 'Tạo kênh bán hàng',
        'flag' => 'products.sale-channel.create',
        'parent_flag' => 'products',
    ],
    [
        'name' => 'Chỉnh sửa kênh bán hàng',
        'flag' => 'products.sale-channel.edit',
        'parent_flag' => 'products',
    ],
    [
        'name' => 'Đối tác vận chuyển',
        'flag' => 'products.delivery-partner',
        'parent_flag' => 'products',
    ],
    [
        'name' => 'Xem đối tác vận chuyển',
        'flag' => 'products.delivery-partner.index',
        'parent_flag' => 'products',
    ],
    [
        'name' => 'Tạo đối tác vận chuyển',
        'flag' => 'products.delivery-partner.create',
        'parent_flag' => 'products',
    ],
    [
        'name' => 'Chỉnh sửa đối tác vận chuyển',
        'flag' => 'products.delivery-partner.edit',
        'parent_flag' => 'products',
    ],

    /*
    |--------------------------------------------------------------------------
    | Xem giá vốn
    |--------------------------------------------------------------------------
    */
    [
        'name' => 'Xem giá vốn sản phẩm',
        'flag' => 'products.view-cost',
        'parent_flag' => 'products',
    ],

    /*
    |--------------------------------------------------------------------------
    | Phương thức thanh toán
    |--------------------------------------------------------------------------
    */
    [
        'name' => 'Phương thức thanh toán',
        'flag' => 'products.payment-method',
        'parent_flag' => 'products',
    ],
    [
        'name' => 'Xem phương thức thanh toán',
        'flag' => 'products.payment-method.index',
        'parent_flag' => 'products',
    ],
    [
        'name' => 'Tạo / Chỉnh sửa phương thức thanh toán',
        'flag' => 'products.payment-method.edit',
        'parent_flag' => 'products',
    ],
];
