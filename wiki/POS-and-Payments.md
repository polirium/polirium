# POS & Thanh toán / VietQR

## POS

- URL: `/admin/products/payment` (và `payment-v2`)
- Livewire: `PaymentComponent` / `PaymentV2Component`
- Chọn phương thức từ bảng `payment_methods`
- Lưu hóa đơn vào `product_payments`

## Phương thức thanh toán

- Admin: `/admin/products/payment-methods`
- Field chính: `name`, `code`, `is_active`, `is_default`, `target_payment_status` (`completed` | `pending`)
- **VietQR:** optional `bank_account_id` — gắn tài khoản ngân hàng

Nếu phương thức có ngân hàng → POS hiện ảnh QR kèm số tiền khi chọn method đó.

## Tài khoản ngân hàng (setting)

- URL: `/admin/products/bank-accounts`
- **Chỉ user id = 1** được vào / thấy menu
- Lưu: số TK, `bank_code` (VietQR), chủ TK, tên cửa hàng, template (`compact` / `qronly` / `standee`)

Bảng: `bank_accounts`.

## Tạo QR công khai

- URL public: **`/vietqr`** (không cần đăng nhập)
- Chọn tài khoản đang active → nhập số tiền + nội dung → xem / tải QR

Ảnh QR theo API:

```text
https://vietqr.app/img?acc=...&bank=...&amount=...&des=...&template=compact&showinfo=true&holder=...&store=...
```

Service: `Polirium\Modules\Product\Support\VietQrService`  
Danh sách bank: `platform/modules/product/config/vietqr_banks.php`

## Luồng cấu hình đề xuất

1. User id 1 thêm tài khoản NH  
2. Gắn tài khoản vào phương thức (vd. Chuyển khoản)  
3. Thu ngân dùng POS → chọn method → quét QR  
4. Hoặc mở `/vietqr` để tạo QR thủ công
