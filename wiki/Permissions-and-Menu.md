# Phân quyền & Menu

## Permission

- Định nghĩa trong `{module}/config/permissions.php`
- Có `flag` và `parent_flag` (cây quyền)
- Translation: `resources/lang/{lang}/permission.php` (hoặc file permission tương ứng)
- Gate / `@can` / middleware `can:flag`

### Ví dụ (accounting)

| Flag | Mục đích |
|------|----------|
| `accountings.payments` | Danh sách hóa đơn bán, báo cáo bán hàng |
| `accountings.invoices` | Hóa đơn mua / liên quan |
| `sales.payment.index` | Vào POS |

> Lưu ý: menu phải dùng đúng flag (vd. `accountings.payments`, không phải `accountings.payment`).

## Menu

- File: `{module}/config/menu.php`
- Gộp bởi `MenuServiceProvider` → menu `polirium.core.menu`
- Field thường dùng:

| Field | Ý nghĩa |
|-------|---------|
| `id` | Unique |
| `name` | Label / translation key |
| `route` | Named route |
| `parent` | id menu cha |
| `icon` | Tabler icon name |
| `permission` | Ẩn nếu user không `can` |
| `user_ids` | Chỉ hiện với các user id (vd. `[1]` cho cấu hình NH) |

### `user_ids`

Dùng cho mục nhạy cảm (cấu hình tài khoản ngân hàng). Áp dụng cả với super admin: chỉ user trong danh sách mới thấy.

## Best practice

1. Route middleware `can:…` khớp permission thật trong DB/config  
2. Menu `permission` khớp cùng flag  
3. Không hard-code ẩn menu trong blade trừ `user_ids`
