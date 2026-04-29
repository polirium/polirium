# Tổng quan hệ thống

## Giới thiệu

Polirium Core là nền tảng modular PHP được xây dựng trên Laravel framework, được thiết kế theo mô hình modular architecture cho phép dễ dàng mở rộng và bảo trì.

## Kiến trúc

```
platform/
├── core/           # Core modules (base, ui, media, settings, support)
├── modules/        # Business modules (tự định nghĩa)
├── packages/       # Third-party packages
└── docs/           # Documentation
```

## Core Packages

| Package    | Mô tả                                                                 |
| ---------- | --------------------------------------------------------------------- |
| `base`     | Core functionality: authentication, users, roles, branches, locations |
| `ui`       | UI components: layouts, forms, tables, modals                         |
| `media`    | Media management: upload, edit, organize files                        |
| `settings` | System settings management                                            |
| `support`  | Base classes và traits cho modules                                    |

## Luồng hoạt động

1. **Bootstrap**: `BaseServiceProvider` load đầu tiên, autoload helpers
2. **Module Loading**: Quét `platform/modules/` và register tất cả providers
3. **Config Merge**: Load và merge config từ core/packages/modules
4. **Route Registration**: Load routes từ mỗi module
5. **View/Translation**: Register views và translations

## Namespace Convention

```
Polirium\Core\{Package}\...     # Core packages
Polirium\Modules\{Module}\...   # Business modules
```
