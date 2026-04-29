<?php

return [
    [
        'id' => 'module_customer',
        'name' => trans('Đối tác'),
        'route' => null,
        'icon' => 'users',
        'sort' => 40,
    ],
    [
        'id' => 'module_customer_list',
        'name' => trans('Khách hàng'),
        'route' => 'customers.index',
        'parent' => 'module_customer',
        'icon' => 'user',
        'sort' => 1,
        'permission' => 'customers.view',
    ],
    [
        'id' => 'module_customer_group_list',
        'name' => trans('Nhóm khách hàng'),
        'route' => 'customers.group',
        'parent' => 'module_customer',
        'icon' => 'user',
        'sort' => 4,
        'permission' => 'customers.groups',
    ],
];
