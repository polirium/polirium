# Helper Functions

Các helper functions được định nghĩa trong `platform/core/base/helpers/base.php` và tự động được autoload.

---

## Path Helpers

### `platform_path($path = null)`

Trả về đường dẫn đến thư mục `platform/`.

```php
platform_path();              // /path/to/project/platform/
platform_path('modules');     // /path/to/project/platform/modules
```

### `core_path($path = null)`

Trả về đường dẫn đến thư mục `platform/core/`.

```php
core_path();                  // /path/to/project/platform/core/
core_path('base/src');        // /path/to/project/platform/core/base/src
```

### `modules_path($path = null)`

Trả về đường dẫn đến thư mục `platform/modules/`.

```php
modules_path();               // /path/to/project/platform/modules/
modules_path('my-module');    // /path/to/project/platform/modules/my-module
```

### `package_path($path = null)`

Trả về đường dẫn đến thư mục `platform/packages/`.

```php
package_path();               // /path/to/project/platform/packages/
```

---

## Admin Helpers

### `admin_prefix($path = null)`

Trả về admin prefix (default: `admin`).

```php
admin_prefix();               // 'admin'
route('core.index');          // /admin
```

Config: `core.base.setting.admin_dir` hoặc `POLIRIUM_ADMIN_DIR` env.

### `core_can($permission)`

Kiểm tra quyền của user hiện tại. Super admin luôn return `true`.

```php
if (core_can('orders.create')) {
    // User có quyền tạo orders
}
```

---

## Location Helpers

### `get_provinces()`

Lấy danh sách tỉnh/thành phố.

```php
$provinces = get_provinces();
// ['1' => 'Hà Nội', '2' => 'TP. Hồ Chí Minh', ...]
```

### `get_districts($province_id)`

Lấy danh sách quận/huyện theo tỉnh.

```php
$districts = get_districts(1);
// ['1' => 'Ba Đình', '2' => 'Hoàn Kiếm', ...]
```

### `get_wards($district_id)`

Lấy danh sách phường/xã theo quận.

```php
$wards = get_wards(1);
// ['1' => 'Phường 1', '2' => 'Phường 2', ...]
```

---

## Branch Helpers

### `user_branch($branch_id = null)`

Lấy hoặc set chi nhánh hiện tại của user.

```php
// Lấy branch hiện tại
$currentBranch = user_branch();

// Set branch mới
user_branch(5);
```

---

## Notification Helpers

### `admin_notification($callback)`

Đăng ký notification cho admin bar. Gọi trong `boot()` của ServiceProvider.

```php
admin_notification(function ($event) {
    $pendingOrders = Order::where('status', 'pending')->count();
    if ($pendingOrders > 0) {
        $event->addNotification([
            'title' => 'Đơn hàng chờ xử lý',
            'description' => "Có {$pendingOrders} đơn hàng cần xử lý",
            'actionUrl' => route('orders.index'),
            'isNew' => true,
            'dotColor' => 'red', // red, green, blue, yellow
        ]);
    }
});
```

---

## Utility Helpers

### `roman_numerals($number)`

Chuyển số thành số la mã.

```php
roman_numerals(4);    // 'IV'
roman_numerals(2024); // 'MMXXIV'
```

### `number_to_text($number)`

Chuyển số thành chữ (tiếng Việt).

```php
number_to_text(1500000);
// 'Một triệu năm trăm nghìn đồng'
```

---

## Assets Helpers

Các helper functions cho quản lý CSS/JS Assets.

### `load_css($names)`

Load CSS asset tùy chọn theo tên.

```php
// Load một asset
load_css('dashboard');

// Load nhiều assets
load_css(['dashboard', 'chart']);
```

### `load_js($names)`

Load JS asset tùy chọn theo tên.

```php
load_js('sortable');
load_js(['sortable', 'dashboard']);
```

### `render_css()`

Render tất cả CSS tags (base + loaded).

```blade
<head>
    {{ render_css() }}
</head>
```

### `render_js()`

Render tất cả JS tags (base + loaded).

```blade
<body>
    @yield('content')
    {{ render_js() }}
</body>
```

### `add_css($assets)`

Thêm CSS vào danh sách cơ bản (luôn load).

```php
// Trong ServiceProvider
add_css([
    'custom' => 'modules/my/css/style.css',
]);
```

### `add_js($assets)`

Thêm JS vào danh sách cơ bản (luôn load).

```php
add_js([
    'custom' => 'modules/my/js/script.js',
]);
```

### `add_optional_css($assets)`

Thêm CSS tùy chọn.

```php
add_optional_css([
    'chart' => 'libs/chartjs/chart.css',
]);
```

### `add_optional_js($assets)`

Thêm JS tùy chọn.

```php
add_optional_js([
    'chartjs' => 'libs/chartjs/chart.min.js',
]);
```

### `register_module_assets($module, $assets)`

Đăng ký assets cho module (tự thêm prefix).

```php
// Trong ModuleServiceProvider
register_module_assets('accounting', [
    'css' => ['css/accounting.css'],
    'optional' => [
        'js' => [
            'invoice' => 'js/invoice.js',
        ],
    ],
]);
```

### `has_asset($name, $type)`

Kiểm tra asset có tồn tại không.

```php
if (has_asset('dashboard', 'css')) {
    // Asset tồn tại
}
```

### `asset_path($path)`

Lấy đường dẫn đầy đủ của asset.

```php
asset_path('core/ui/css/style.css');
// https://example.com/vendor/polirium/core/ui/css/style.css
```

---

*Xem thêm: [Assets System](./10-assets-system.md) để biết chi tiết về quản lý Assets.*
