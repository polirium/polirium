<?php

return [
    [
        'id' => 'module_product',
        'name' => trans('Hàng hoá'),
        'route' => null,
        'icon' => 'category-filled',
        'sort' => 10,
        // Parent menus auto-hide if no children visible
    ],
    [
        'id' => 'module_product_index',
        'name' => trans('Danh mục'),
        'route' => 'products.index',
        'parent' => 'module_product',
        'icon' => 'category-filled',
        'sort' => 1,
        'permission' => 'products.index',
    ],
    [
        'id' => 'module_product_price_setting',
        'name' => trans('Thiết lập giá'),
        'route' => 'products.price-setting',
        'parent' => 'module_product',
        'icon' => 'currency-dollar',
        'sort' => 2,
        'permission' => 'products.price-setting',
    ],
    [
        'id' => 'module_product_stock',
        'name' => trans('Kiểm kho'),
        'route' => 'products.stock.index',
        'parent' => 'module_product',
        'icon' => 'device-desktop-check',
        'sort' => 3,
        'permission' => 'products.stock',
    ],
    [
        'id' => 'module_product_sale_channel',
        'name' => trans('Kênh bán hàng'),
        'route' => 'products.sale-channel.index',
        'parent' => 'module_customer',
        'icon' => 'building-store',
        'sort' => 5,
        'permission' => 'products.sale-channel',
    ],
    [
        'id' => 'module_product_delivery_partner',
        'name' => trans('Đối tác giao hàng'),
        'route' => 'products.delivery-partner.index',
        'parent' => 'module_customer',
        'icon' => 'truck',
        'sort' => 6,
        'permission' => 'products.delivery-partner',
    ],
];
