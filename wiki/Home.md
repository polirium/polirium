# Polirium ERP Platform

**Polirium** là nền tảng ERP modular trên Laravel, UI Tabler + Livewire/Alpine, hỗ trợ đa chi nhánh và phân quyền theo role.

## Tech stack

| Thành phần | Phiên bản / công nghệ |
|------------|------------------------|
| PHP | ^8.3 |
| Laravel | ^13 |
| Frontend | Livewire, Alpine.js, Tabler UI |
| Excel | Maatwebsite Excel |
| DB | MySQL / PostgreSQL |

## Bản đồ nhanh

```text
polirium/
├── app/                 # Shell Laravel mỏng
├── platform/
│   ├── core/            # base, ui, media, settings, support
│   ├── modules/         # product, sale, customer, vendor, accounting, …
│   ├── packages/        # tabler-icons, impersonate, datatable
│   └── docs/            # Tài liệu kỹ thuật chi tiết
├── wiki/                # Nội dung GitHub Wiki (thư mục này)
└── composer.json        # path repos → platform/*
```

## Mục lục wiki

> Nếu không thấy menu trái: mở **Pages** (góc phải trang Wiki) để xem toàn bộ trang.

1. [Cài đặt](Getting-Started)
2. [Kiến trúc](Architecture)
3. [Modules nghiệp vụ](Modules)
4. [Phân quyền & Menu](Permissions-and-Menu)
5. [POS & Thanh toán / VietQR](POS-and-Payments)
6. [Kiểm kho](Stock-Check)
7. [Coding standards](Coding-Standards)
8. [Deploy server](Deployment)

## Tài liệu trong repo

Chi tiết hơn xem `platform/docs/` (overview, module, helpers, permission, UI, …).

## Admin mặc định (local)

- URL: `/admin`
- User mẫu (nếu seed): xem README project

---

*Wiki được tạo từ mã nguồn Polirium — cập nhật khi kiến trúc thay đổi.*
