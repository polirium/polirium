# Modules nghiệp vụ

Module nằm trong `platform/modules/`, mỗi module là package Laravel độc lập.

## Danh sách hiện có

| Module | Domain |
|--------|--------|
| **product** | Sản phẩm, giá, kiểm kho, POS payment, VietQR, kênh bán, ĐTGH |
| **sale** | Quyền / nghiệp vụ bán hàng (permission POS) |
| **customer** | Khách hàng |
| **vendor** | Nhà cung cấp, nhập hàng, hoàn NCC |
| **accounting** | Hóa đơn bán, báo cáo, sổ quỹ |
| **print-forms** | Mẫu in |
| **task** | Task / Kanban / Gantt |

## Cấu trúc module chuẩn

```text
module-name/
├── composer.json
├── config/          # menu.php, permissions.php, livewire.php, …
├── database/migrations/
├── helpers/
├── resources/views|lang/
├── routes/web.php
└── src/
    ├── Providers/
    ├── Http/Controllers|Livewire|Model|Requests/
    └── Support|Service/
```

## Tạo module mới (tóm tắt)

1. Tạo thư mục + `composer.json` (provider + PSR-4)
2. `ServiceProvider` dùng `setNamespace(...)->load*`
3. Khai báo `config/permissions.php` + translation permission
4. `config/menu.php` nếu cần menu
5. `composer dump-autoload`

Hướng dẫn đầy đủ: `platform/docs/03-creating-modules.md`.

## Product — điểm vào quan trọng

| URL (admin) | Việc |
|-------------|------|
| `/admin/products` | Danh mục SP |
| `/admin/products/payment` | POS bán hàng |
| `/admin/products/payment-methods` | Phương thức thanh toán |
| `/admin/products/bank-accounts` | TK ngân hàng VietQR (**chỉ user id = 1**) |
| `/admin/products/stock` | Kiểm kho |
| `/vietqr` | Tạo QR công khai (không cần login) |
