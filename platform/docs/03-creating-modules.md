# Cách viết Module

## Tổng quan

Module trong Polirium được tự động load từ thư mục `platform/modules/`. Mỗi module cần tuân theo cấu trúc chuẩn và khai báo ServiceProvider.

## Bước 1: Tạo cấu trúc thư mục

```bash
mkdir -p platform/modules/my-module/{config,database/migrations,helpers,resources/{views,lang},routes,src/{Providers,Http/{Controllers,Livewire,Models}}}
```

## Bước 2: Tạo composer.json

```json
{
    "name": "polirium/my-module",
    "description": "My custom module",
    "extra": {
        "laravel": {
            "providers": [
                "Polirium\\Modules\\MyModule\\Providers\\MyModuleServiceProvider"
            ]
        }
    },
    "autoload": {
        "psr-4": {
            "Polirium\\Modules\\MyModule\\": "src/"
        }
    }
}
```

## Bước 3: Tạo ServiceProvider

```php
<?php
// src/Providers/MyModuleServiceProvider.php

namespace Polirium\Modules\MyModule\Providers;

use Polirium\Core\Support\Providers\PoliriumBaseServiceProvider;

class MyModuleServiceProvider extends PoliriumBaseServiceProvider
{
    public function boot()
    {
        $this->setNamespace('modules/my-module')
            ->loadConfigurations(['setting'])  // Load config/setting.php
            ->loadViews()                       // Load resources/views/
            ->loadTranslations()                // Load resources/lang/
            ->loadRoutes(['web', 'api'])        // Load routes/web.php, api.php
            ->loadMigrations();                 // Load database/migrations/
    }

    public function register()
    {
        // Register bindings, singletons, etc.
    }
}
```

## Bước 4: Tạo Routes

```php
<?php
// routes/web.php

use Illuminate\Support\Facades\Route;
use Polirium\Modules\MyModule\Http\Controllers\MyController;

Route::middleware(['web', 'auth'])
    ->prefix(admin_prefix() . '/my-module')
    ->name('my-module.')
    ->group(function () {
        Route::get('/', [MyController::class, 'index'])->name('index');
    });
```

## Bước 5: Tạo Controller

```php
<?php
// src/Http/Controllers/MyController.php

namespace Polirium\Modules\MyModule\Http\Controllers;

use Illuminate\Routing\Controller;

class MyController extends Controller
{
    public function index()
    {
        return view('modules/my-module::index');
    }
}
```

## Bước 6: Tạo View

```blade
{{-- resources/views/index.blade.php --}}
<x-ui.layouts::app>
    <x-slot:title>My Module</x-slot:title>

    <x-ui::card header="My Module">
        <p>Content here</p>
    </x-ui::card>
</x-ui.layouts::app>
```

## LoadAndPublishDataTrait Methods

| Method                       | Mô tả                                                              |
| ---------------------------- | ------------------------------------------------------------------ |
| `setNamespace($namespace)`   | Set namespace cho module (vd: `modules/my-module`)                 |
| `loadConfigurations($files)` | Load config files (tự động thêm `livewire`, `menu`, `permissions`) |
| `loadViews()`                | Load views từ `resources/views/`                                   |
| `loadTranslations()`         | Load translations từ `resources/lang/`                             |
| `loadRoutes($files)`         | Load routes từ `routes/`                                           |
| `loadMigrations()`           | Load migrations từ `database/migrations/`                          |
| `publishAssets($path)`       | Publish public assets                                              |

## Module được load như thế nào?

1. `BaseServiceProvider::registerModules()` quét thư mục `platform/modules/`
2. Đọc `composer.json` của mỗi module
3. Register autoload PSR-4 namespace
4. Register providers từ `extra.laravel.providers`
