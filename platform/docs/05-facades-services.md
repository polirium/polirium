# Facades & Services

## LocationHelper Facade

Facade để truy cập LocationService, cung cấp thông tin địa lý Việt Nam.

**Namespace:** `Polirium\Core\Base\Facade\LocationHelper`

### Methods

| Method                       | Parameters         | Return  | Mô tả                    |
| ---------------------------- | ------------------ | ------- | ------------------------ |
| `getProvinces()`             | -                  | `array` | Lấy danh sách tỉnh/thành |
| `getDistricts($province_id)` | `int $province_id` | `array` | Lấy danh sách quận/huyện |
| `getWards($district_id)`     | `int $district_id` | `array` | Lấy danh sách phường/xã  |

### Sử dụng

```php
use Polirium\Core\Base\Facade\LocationHelper;

$provinces = LocationHelper::getProvinces();
$districts = LocationHelper::getDistricts(1);
$wards = LocationHelper::getWards(1);
```

Hoặc dùng helper functions:

```php
$provinces = get_provinces();
$districts = get_districts(1);
$wards = get_wards(1);
```

> **Note:** Dữ liệu được cache 30 ngày để tối ưu performance.

---

## BaseHelper Class

Helper class cho các tác vụ cơ bản.

**Namespace:** `Polirium\Core\Base\Helpers\BaseHelper`

### Methods

#### `autoload($directory)`

Tự động load tất cả file PHP trong thư mục.

```php
BaseHelper::autoload(__DIR__ . '/../../helpers');
```

#### `scanFolder($path, $ignoreFiles = [])`

Quét thư mục và trả về danh sách files/folders.

```php
$modules = BaseHelper::scanFolder(platform_path('modules'));
// ['module-a', 'module-b', ...]

// Bỏ qua một số files
$modules = BaseHelper::scanFolder($path, ['README.md', '.gitignore']);
```

#### `getAdminPrefix()`

Lấy admin prefix từ config.

```php
$prefix = BaseHelper::getAdminPrefix(); // 'admin'
```

---

## LocationService

Service class cho LocationHelper Facade.

**Namespace:** `Polirium\Core\Base\Service\LocationService`

### Implementation Details

- Sử dụng cache 30 ngày (2592000 giây)
- Query tối ưu với select chỉ `id`, `name`
- Return dạng key-value array để dùng với form select

### Models liên quan

- `Province` - Tỉnh/Thành phố
- `District` - Quận/Huyện
- `Ward` - Phường/Xã

---

## Tạo Facade mới

### Bước 1: Tạo Service Class

```php
<?php
// src/Service/MyService.php

namespace Polirium\Modules\MyModule\Service;

class MyService
{
    public function doSomething()
    {
        return 'result';
    }
}
```

### Bước 2: Tạo Facade

```php
<?php
// src/Facade/MyFacade.php

namespace Polirium\Modules\MyModule\Facade;

use Illuminate\Support\Facades\Facade;
use Polirium\Modules\MyModule\Service\MyService;

class MyFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return MyService::class;
    }
}
```

### Bước 3: Sử dụng

```php
use Polirium\Modules\MyModule\Facade\MyFacade;

$result = MyFacade::doSomething();
```
