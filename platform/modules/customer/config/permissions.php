<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Quản lý Khách hàng
    |--------------------------------------------------------------------------
    */
    [
        'name' => 'Khách hàng',
        'flag' => 'customers',
    ],
    [
        'name' => 'Xem danh sách khách hàng',
        'flag' => 'customers.index',
        'parent_flag' => 'customers',
    ],
    [
        'name' => 'Tạo khách hàng',
        'flag' => 'customers.create',
        'parent_flag' => 'customers',
    ],
    [
        'name' => 'Sửa khách hàng',
        'flag' => 'customers.edit',
        'parent_flag' => 'customers',
    ],
    [
        'name' => 'Xoá khách hàng',
        'flag' => 'customers.destroy',
        'parent_flag' => 'customers',
    ],
    [
        'name' => 'Nhóm khách hàng',
        'flag' => 'customers.groups',
        'parent_flag' => 'customers',
    ],
];
