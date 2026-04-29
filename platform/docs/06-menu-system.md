# Menu System

## Tổng quan

Menu trong Polirium được định nghĩa bằng config file `config/menu.php` trong mỗi module. Hệ thống tự động scan và merge menu từ tất cả core/packages/modules.

## Config File

Tạo file `config/menu.php` trong module:

```php
<?php

return [
    // Menu cha (parent)
    [
        'id' => 'my-module',
        'name' => trans('My Module'),
        'route' => null,           // null nếu là parent menu
        'icon' => 'package',       // Tabler icon name
        'sort' => 10,              // Thứ tự sắp xếp
    ],
    // Menu con (children)
    [
        'id' => 'my-module.list',
        'name' => trans('Danh sách'),
        'parent' => 'my-module',   // Chỉ định parent
        'route' => 'my-module.index',
        'icon' => 'list',
        'sort' => 1,
    ],
    [
        'id' => 'my-module.create',
        'name' => trans('Tạo mới'),
        'parent' => 'my-module',
        'route' => 'my-module.create',
        'icon' => 'plus',
        'sort' => 2,
    ],
];
```

## Menu Item Structure

| Key          | Type         | Required | Mô tả                                 |
| ------------ | ------------ | -------- | ------------------------------------- |
| `id`         | string       | ✅       | Unique identifier                     |
| `name`       | string       | ✅       | Display name (use `trans()` for i18n) |
| `route`      | string\|null | ✅       | Route name hoặc `null` nếu là parent  |
| `icon`       | string       | ❌       | Tabler icon name                      |
| `parent`     | string       | ❌       | Parent menu id (default: `root`)      |
| `sort`       | int          | ❌       | Sort order (default: `0`)             |
| `permission` | string       | ❌       | Permission flag để kiểm tra quyền     |

## Cách hoạt động

1. **Scan**: `GetMenuDataTrait::getAvailableMenus()` quét tất cả modules
2. **Merge**: Merge menu từ `config('{type}.{module}.menu')`
3. **Tree**: `getMenuTree()` xây dựng tree structure từ parent-child
4. **Render**: `MenuServiceProvider` render menu trong sidebar

## Trait: GetMenuDataTrait

```php
use Polirium\Core\Base\Traits\GetMenuDataTrait;

class MyClass
{
    use GetMenuDataTrait;

    public function getAllMenus()
    {
        $menus = $this->getAvailableMenus();
        $tree = $this->getMenuTree($menus);
        return $tree;
    }
}
```

### Methods

| Method                                | Return  | Mô tả                                      |
| ------------------------------------- | ------- | ------------------------------------------ |
| `getAvailableMenus()`                 | `array` | Lấy tất cả menu items từ all modules       |
| `getAvailableMenuForEachType($type)`  | `array` | Lấy menu theo type (core/packages/modules) |
| `getMenuTree($menus)`                 | `array` | Xây dựng tree từ flat menu array           |
| `getChildren($parentFlag, $allFlags)` | `array` | Lấy children của một menu                  |

## Icons

Sử dụng Tabler Icons: https://tabler-icons.io/

```php
'icon' => 'home'        // ti-home
'icon' => 'settings'    // ti-settings
'icon' => 'users'       // ti-users
```
