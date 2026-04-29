# Assets System

Hệ thống quản lý Assets (CSS/JS) của Polirium Core, hỗ trợ đăng ký và load assets một cách linh hoạt.

---

## Tổng quan

Assets System nằm trong `platform/core/ui` với các thành phần chính:

- **Assets Class** - `platform/core/ui/src/Support/Assets.php`
- **Assets Facade** - `platform/core/ui/src/Facades/Assets.php`
- **Assets Config** - `platform/core/ui/config/assets.php`
- **Helper Functions** - `platform/core/ui/helpers/ui.php`

---

## Cấu trúc Assets

### Base Assets (Luôn được load)

Các assets này sẽ được load trong mọi trang, được định nghĩa trong config:

```php
// config/core/ui/assets.php
return [
    'css' => [
        'core' => 'core/ui/css/polirium-core.min.css',
        'vendors' => 'core/ui/css/polirium-vendors.min.css',
    ],
    'js' => [
        'polirium' => 'core/ui/js/polirium.min.js',
        'theme' => 'core/ui/js/theme.min.js',
    ],
];
```

### Optional Assets (Chỉ load khi cần)

Các assets này chỉ được render khi được gọi:

```php
'optional' => [
    'css' => [
        'dashboard' => 'core/base/css/dashboard.css',
    ],
    'js' => [
        'sortable' => 'core/base/js/vendor/sortable.min.js',
        'dashboard' => 'core/base/js/dashboard.js',
    ],
],
```

---

## Sử dụng trong ServiceProvider

### Cách 1: Đăng ký trực tiếp với Facade

```php
use Polirium\Core\UI\Facades\Assets;

class AccountingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Đăng ký CSS luôn load cho module này
        Assets::addCss([
            'accounting' => 'modules/accounting/css/accounting.css',
        ]);

        // Đăng ký JS tùy chọn
        Assets::addOptionalJs([
            'invoice' => 'modules/accounting/js/invoice.js',
            'chart' => 'modules/accounting/js/chart.js',
        ]);
    }
}
```

### Cách 2: Dùng helper `register_module_assets()`

Helper này tự động thêm prefix `modules/{module_name}/` vào đường dẫn:

```php
class AccountingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        register_module_assets('accounting', [
            // CSS luôn load
            'css' => [
                'css/accounting.css', // → modules/accounting/css/accounting.css
            ],

            // JS tùy chọn
            'optional' => [
                'js' => [
                    'invoice' => 'js/invoice.js',  // → modules/accounting/js/invoice.js
                    'chart' => 'js/chart.js',      // → modules/accounting/js/chart.js
                ],
            ],
        ]);
    }
}
```

### Cách 3: Dùng Helper Functions

```php
use function Polirium\Core\UI\helpers\add_css;
use function Polirium\Core\UI\helpers\add_optional_js;

class MyServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        add_css([
            'custom' => 'modules/my/css/custom.css',
        ]);

        add_optional_js([
            'feature' => 'modules/my/js/feature.js',
        ]);
    }
}
```

---

## Sử dụng trong Blade View

### Load Assets khi cần

```blade
{{-- Load một asset --}}
@php
    load_css('dashboard');
    load_js('sortable');
@endphp

{{-- Load nhiều assets cùng lúc --}}
@php
    load_js(['sortable', 'dashboard', 'chart']);
@endphp
```

### Hoặc dùng Facade

```blade
@php
    \Polirium\Core\UI\Facades\Assets::loadJs(['sortable', 'dashboard']);
@endphp
```

### Render trong Layout

Trong `base.blade.php`:

```blade
<!doctype html>
<html>
<head>
    {{ render_css() }}
</head>
<body>
    @yield('content')

    {{ render_js() }}
</body>
</html>
```

---

## API Reference

### Assets Facade Methods

| Method | Parameters | Return | Mô tả |
|--------|-----------|--------|-------|
| `addCss($assets)` | `array $assets` | `Assets` | Thêm CSS luôn load |
| `addJs($assets)` | `array $assets` | `Assets` | Thêm JS luôn load |
| `addOptionalCss($assets)` | `array $assets` | `Assets` | Thêm CSS tùy chọn |
| `addOptionalJs($assets)` | `array $assets` | `Assets` | Thêm JS tùy chọn |
| `loadCss($names)` | `string\|array $names` | `Assets` | Kích hoạt load CSS |
| `loadJs($names)` | `string\|array $names` | `Assets` | Kích hoạt load JS |
| `renderCss()` | - | `HtmlString` | Render CSS tags |
| `renderJs()` | - | `HtmlString` | Render JS tags |
| `registerModuleAssets($module, $assets)` | `string, array` | `Assets` | Đăng ký module assets |
| `has($name, $type)` | `string, string` | `bool` | Kiểm tra asset tồn tại |
| `path($name, $type)` | `string, string` | `string\|null` | Lấy path asset |
| `get($path)` | `string $path` | `string` | Lấy full URL |

### Helper Functions

| Function | Parameters | Return | Mô tả |
|----------|-----------|--------|-------|
| `render_css()` | - | `HtmlString` | Render CSS tags |
| `render_js()` | - | `HtmlString` | Render JS tags |
| `load_css($names)` | `string\|array` | `Assets` | Load CSS |
| `load_js($names)` | `string\|array` | `Assets` | Load JS |
| `add_css($assets)` | `array` | `Assets` | Thêm CSS luôn load |
| `add_js($assets)` | `array` | `Assets` | Thêm JS luôn load |
| `add_optional_css($assets)` | `array` | `Assets` | Thêm CSS tùy chọn |
| `add_optional_js($assets)` | `array` | `Assets` | Thêm JS tùy chọn |
| `register_module_assets($module, $assets)` | `string, array` | `Assets` | Đăng ký module assets |
| `has_asset($name, $type)` | `string, string` | `bool` | Kiểm tra tồn tại |
| `asset_path($path)` | `string` | `string` | Lấy full URL |

---

## Ví dụ thực tế

### Dashboard Widget

**Trong ServiceProvider:**

```php
class DashboardServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Đăng ký assets cho dashboard
        Assets::addOptionalCss([
            'dashboard' => 'core/base/css/dashboard.css',
        ]);

        Assets::addOptionalJs([
            'sortable' => 'core/base/js/vendor/sortable.min.js',
            'dashboard' => 'core/base/js/dashboard.js',
        ]);
    }
}
```

**Trong View (`dashboard-component.blade.php`):**

```blade
@php
    load_css('dashboard');
    load_js(['sortable', 'dashboard']);
@endphp

<div class="dashboard-container">
    {{-- Dashboard content --}}
</div>
```

### Module với Feature cụ thể

**Accounting Module:**

```php
class AccountingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // CSS chung cho toàn module
        Assets::addCss([
            'accounting' => 'modules/accounting/css/accounting.css',
        ]);

        // JS chỉ dùng ở trang tạo invoice
        Assets::addOptionalJs([
            'invoice-form' => 'modules/accounting/js/invoice-form.js',
            'invoice-chart' => 'modules/accounting/js/invoice-chart.js',
        ]);
    }
}
```

**Trong Invoice Create View:**

```blade
@php
    load_js(['invoice-form', 'invoice-chart']);
@endphp

<form>
    {{-- Invoice form --}}
</form>
```

---

## Đường dẫn Assets

### Quy tắc đường dẫn

Tất cả đường dẫn assets là **tương đối** từ `public/vendor/polirium/`:

```php
// Đúng
Assets::addCss([
    'custom' => 'core/ui/css/custom.css',
    'module' => 'modules/accounting/css/style.css',
]);

// Sai (không có public/vendor/polirium/)
Assets::addCss([
    'custom' => '/public/vendor/polirium/core/ui/css/custom.css',
]);
```

### Cấu trúc thư mục

```
public/vendor/polirium/
├── core/
│   ├── base/
│   │   ├── css/
│   │   │   └── dashboard.css
│   │   └── js/
│   │       ├── dashboard.js
│   │       └── vendor/
│   │           └── sortable.min.js
│   └── ui/
│       ├── css/
│       │   ├── polirium-core.min.css
│       │   └── polirium-vendors.min.css
│       ├── js/
│       │   ├── polirium.min.js
│       │   └── app.min.js
│       └── libs/
│           └── dropzone/
└── modules/
    ├── accounting/
    │   ├── css/
    │   │   └── accounting.css
    │   └── js/
    │       └── invoice.js
    └── product/
        └── css/
            └── product.css
```

---

## Tips & Best Practices

### 1. Sử dụng Optional Assets

Chỉ load assets khi cần để tối ưu performance:

```php
// ✅ Tốt - Chỉ load khi cần
Assets::addOptionalJs([
    'chart' => 'libs/chartjs/chart.min.js',
]);

// ❌ Không tốt - Load luôn dù không dùng everywhere
Assets::addJs([
    'chart' => 'libs/chartjs/chart.min.js',
]);
```

### 2. Đặt tên Assets rõ nghĩa

```php
// ✅ Tốt - Tên rõ nghĩa
Assets::addOptionalJs([
    'invoice-chart' => 'modules/accounting/js/invoice-chart.js',
    'invoice-form' => 'modules/accounting/js/invoice-form.js',
]);

// ❌ Không tốt - Tên chung chung
Assets::addOptionalJs([
    'chart' => 'modules/accounting/js/invoice-chart.js',
    'script' => 'modules/accounting/js/invoice-form.js',
]);
```

### 3. Group related assets

```php
// Load nhóm assets liên quan
@php
    load_js(['invoice-form', 'invoice-chart', 'invoice-validate']);
@endphp
```

### 4. Kiểm tra asset trước khi load

```php
@if(has_asset('dashboard', 'css'))
    @php(load_css('dashboard'))
@endif
```

---

## Troubleshooting

### Assets không được load

1. **Kiểm tra config đã clear:**

   ```bash
   php artisan config:clear
   ```

2. **Kiểm tra ServiceProvider đã được register:**

   ```bash
   php artisan package:discover
   php artisan optimize:clear
   ```

3. **Kiểm tra đường dẫn file tồn tại:**

   ```bash
   ls -la public/vendor/polirium/modules/accounting/css/
   ```

### Debug với `has()` và `path()`

```php
@php
    dump(Assets::has('dashboard', 'css'));      // true/false
    dump(Assets::path('dashboard', 'css'));     // đường dẫn
@endphp
```
