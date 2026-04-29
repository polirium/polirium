# Permission System

## Tổng quan

Permissions trong Polirium được định nghĩa bằng config file `config/permissions.php`. Hệ thống hỗ trợ hierarchical permissions (parent-child).

## Config File

Tạo file `config/permissions.php` trong module:

```php
<?php

return [
    // Parent permission
    [
        'name' => trans('My Module'),
        'flag' => 'my-module',
    ],
    // Child permissions
    [
        'name' => trans('Xem danh sách'),
        'flag' => 'my-module.index',
        'parent_flag' => 'my-module',
    ],
    [
        'name' => trans('Tạo mới'),
        'flag' => 'my-module.create',
        'parent_flag' => 'my-module',
    ],
    [
        'name' => trans('Chỉnh sửa'),
        'flag' => 'my-module.edit',
        'parent_flag' => 'my-module',
    ],
    [
        'name' => trans('Xóa'),
        'flag' => 'my-module.delete',
        'parent_flag' => 'my-module',
    ],
];
```

## Permission Item Structure

| Key           | Type   | Required | Mô tả                                    |
| ------------- | ------ | -------- | ---------------------------------------- |
| `name`        | string | ✅       | Display name (use `trans()`)             |
| `flag`        | string | ✅       | Unique permission flag                   |
| `parent_flag` | string | ❌       | Parent permission flag (default: `root`) |

## Kiểm tra quyền

### Sử dụng helper

```php
if (core_can('my-module.create')) {
    // User có quyền tạo
}
```

### Sử dụng middleware

```php
Route::middleware(['auth', 'can:my-module.index'])
    ->get('/my-module', [MyController::class, 'index']);
```

### Trong Blade

```blade
@can('my-module.create')
    <button>Tạo mới</button>
@endcan
```

## Trait: GetPermission

```php
use Polirium\Core\Base\Traits\GetPermission;

class MyClass
{
    use GetPermission;

    public function getAllPermissions()
    {
        $permissions = $this->getAvailablePermissions();
        $tree = $this->getPermissionTree($permissions);
        return $tree;
    }
}
```

### Methods

| Method                                     | Return  | Mô tả                     |
| ------------------------------------------ | ------- | ------------------------- |
| `getAvailablePermissions()`                | `array` | Lấy tất cả permissions    |
| `getAvailablePermissionForEachType($type)` | `array` | Lấy permissions theo type |
| `getPermissionTree($permissions)`          | `array` | Xây dựng tree structure   |
| `getChildren($parentFlag, $allFlags)`      | `array` | Lấy children permissions  |

## Super Admin

Super Admin luôn bypass mọi permission check:

```php
function core_can(string $permissions): bool
{
    if (auth()->user()->isSuperAdmin()) {
        return true;
    }
    return auth()->user()->can($permissions);
}
```

## Best Practices

1. **Naming convention**: `{module}.{action}` (vd: `orders.create`)
2. **Hierarchical**: Luôn có parent permission cho module
3. **Granular**: Tách biệt read/create/edit/delete permissions
4. **Translations**: Sử dụng `trans()` cho i18n support
