# Kiến trúc

## Tổng quan

Polirium tách **shell Laravel** (`app/`, `routes/`, …) và **platform** chứa core + modules + packages.

```text
platform/
├── core/        # Nền tảng
├── modules/     # Nghiệp vụ
├── packages/    # Thư viện phụ trợ
└── docs/        # Tài liệu kỹ thuật
```

## Core (`platform/core`)

| Package | Vai trò |
|---------|---------|
| **base** | Auth, User/Role, branch, permission, bootstrap modules |
| **ui** | Layout Tabler, form/table/modal, assets |
| **media** | Upload / Spatie Media |
| **settings** | Cấu hình hệ thống (`Settings` facade) |
| **support** | Traits, base providers, helpers load |

## Bootstrap module

1. `BaseServiceProvider` khởi động
2. Quét `platform/modules/*/composer.json`
3. Register provider + autoload PSR-4
4. Merge config, load routes / views / migrations / translations

Trait chuẩn: `LoadAndPublishDataTrait`

```php
$this->setNamespace('modules/product')
    ->loadConfigurations([...])
    ->loadViews()
    ->loadTranslations()
    ->loadRoutes(['web'])
    ->loadMigrations();
```

## Namespace

```text
Polirium\Core\{Package}\...
Polirium\Modules\{Module}\...
Polirium\{Package}\...          # packages
```

## UI & Livewire

- Layout: `core/ui` + Tabler
- Icon: `{{ tabler_icon('name') }}` (không dùng `@svg`)
- Component Livewire đăng ký trong `{module}/config/livewire.php`

## Facades thường dùng

- `Settings` — cấu hình
- `Assets` — CSS/JS
- `CoreSupport` — hỗ trợ core

Chi tiết: `platform/docs/01-overview.md`, `05-facades-services.md`.
