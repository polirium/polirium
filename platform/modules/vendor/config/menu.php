<?php

return [
    [
        'id' => 'module_vendor_index',
        'name' => trans('modules/vendor::vendor.name'),
        'route' => 'vendors.index',
        'parent' => 'module_customer',
        'icon' => 'truck-delivery',
        'sort' => 3,
        'permission' => 'vendors.view',
    ],

    [
        'id' => 'module_vendor_group',
        'name' => trans('modules/vendor::vendor.group.name'),
        'route' => 'vendors.group',
        'parent' => 'module_customer',
        'icon' => 'user',
        'sort' => 2,
        'permission' => 'vendors.groups',
    ],

    [
        'id' => 'module_vendor_trade',
        'name' => 'Nghiệp vụ',
        'route' => null,
        'icon' => 'users',
        'sort' => 25,
    ],
    [
        'id' => 'module_vendor_purchase',
        'name' => trans('modules/vendor::purchase.name'),
        'route' => 'vendors.purchases.index',
        'parent' => 'module_vendor_trade',
        'icon' => 'user',
        'sort' => 0,
        'permission' => 'vendors.purchases.index',
    ],
    [
        'id' => 'module_vendor_transfer',
        'name' => trans('modules/vendor::transfer.name'),
        'route' => 'vendors.transfers.index',
        'parent' => null, // Promote to top level
        'icon' => 'truck-delivery', // Assign icon
        'sort' => 30,
        'permission' => 'transfers.view',
    ],
    [
        'id' => 'module_vendor_purchase_transfer',
        'name' => trans('modules/vendor::purchase.refund.name'),
        'route' => 'vendors.purchases.list-refunds',
        'parent' => 'module_vendor_trade',
        'icon' => 'user',
        'sort' => 0,
        'permission' => 'vendors.refunds.index',
    ],
];
