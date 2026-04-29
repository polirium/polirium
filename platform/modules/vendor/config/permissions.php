<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Quản lý Nhà cung cấp
    |--------------------------------------------------------------------------
    */
    [
        'name' => 'Nhà cung cấp',
        'flag' => 'vendors',
    ],
    [
        'name' => 'Xem danh sách nhà cung cấp',
        'flag' => 'vendors.index',
        'parent_flag' => 'vendors',
    ],
    [
        'name' => 'Thêm nhà cung cấp',
        'flag' => 'vendors.create',
        'parent_flag' => 'vendors',
    ],
    [
        'name' => 'Sửa nhà cung cấp',
        'flag' => 'vendors.edit',
        'parent_flag' => 'vendors',
    ],
    [
        'name' => 'Xóa nhà cung cấp',
        'flag' => 'vendors.delete',
        'parent_flag' => 'vendors',
    ],
    [
        'name' => 'Xem danh sách nhóm nhà cung cấp',
        'flag' => 'vendors.groups',
        'parent_flag' => 'vendors',
    ],

    /*
    |--------------------------------------------------------------------------
    | Nhập hàng
    |--------------------------------------------------------------------------
    */
    [
        'name' => 'Xem danh sách nhập hàng',
        'flag' => 'vendors.purchases.index',
        'parent_flag' => 'vendors',
    ],
    [
        'name' => 'Tạo phiếu nhập',
        'flag' => 'vendors.purchases.create',
        'parent_flag' => 'vendors',
    ],
    [
        'name' => 'Xem phiếu nhập kho',
        'flag' => 'vendors.purchases.view',
        'parent_flag' => 'vendors',
    ],
    [
        'name' => 'Chỉnh sửa phiếu nhập kho',
        'flag' => 'vendors.purchases.edit',
        'parent_flag' => 'vendors',
    ],
    [
        'name' => 'Xóa phiếu nhập kho',
        'flag' => 'vendors.purchases.delete',
        'parent_flag' => 'vendors',
    ],
    [
        'name' => 'Xem giá nhập',
        'flag' => 'vendors.purchases.view-price',
        'parent_flag' => 'vendors',
    ],

    /*
    |--------------------------------------------------------------------------
    | Trả hàng
    |--------------------------------------------------------------------------
    */
    [
        'name' => 'Trả hàng nhà cung cấp',
        'flag' => 'vendors.refunds.index',
        'parent_flag' => 'vendors',
    ],
    [
        'name' => 'Xem phiếu trả hàng nhập',
        'flag' => 'vendors.refunds.view',
        'parent_flag' => 'vendors',
    ],
    [
        'name' => 'Chỉnh sửa phiếu trả hàng nhập',
        'flag' => 'vendors.refunds.edit',
        'parent_flag' => 'vendors',
    ],
    [
        'name' => 'Xóa phiếu trả hàng nhập',
        'flag' => 'vendors.refunds.delete',
        'parent_flag' => 'vendors',
    ],
    [
        'name' => 'Xem giá trả',
        'flag' => 'vendors.refunds.view-price',
        'parent_flag' => 'vendors',
    ],

    /*
    |--------------------------------------------------------------------------
    | Chuyển hàng
    |--------------------------------------------------------------------------
    */
    [
        'name' => 'Chuyển hàng',
        'flag' => 'vendors.transfers.index',
        'parent_flag' => 'vendors',
    ],
    [
        'name' => 'Tạo phiếu chuyển hàng',
        'flag' => 'vendors.transfers.create',
        'parent_flag' => 'vendors',
    ],
    [
        'name' => 'Xem phiếu chuyển hàng',
        'flag' => 'vendors.transfers.view',
        'parent_flag' => 'vendors',
    ],
    [
        'name' => 'Chỉnh sửa phiếu chuyển hàng',
        'flag' => 'vendors.transfers.edit',
        'parent_flag' => 'vendors',
    ],
    [
        'name' => 'Xóa phiếu chuyển hàng',
        'flag' => 'vendors.transfers.delete',
        'parent_flag' => 'vendors',
    ],
    [
        'name' => 'Xem giá chuyển',
        'flag' => 'vendors.transfers.view-price',
        'parent_flag' => 'vendors',
    ],
];
