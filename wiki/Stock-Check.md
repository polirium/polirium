# Kiểm kho

## Phiếu kiểm kho

- Menu: Hàng hoá → Kiểm kho  
- Draft / completed / cancelled  
- Cột: Tồn hệ thống (`amount` từ `product_branches.qty` theo chi nhánh phiếu), Thực tế (`actual_stock`), chênh lệch

## Import Excel

- Nút **Nhập từ Excel** trên phiếu nháp  
- File mẫu: **Mã hàng** + **Số lượng thực tế** (CSV/XLSX)  
- Class: `Polirium\Modules\Product\Imports\StockImport`

### Header được nhận (slug)

Mã: `ma_hang`, `code`, `ma_sp`, …  
SL: `so_luong_thuc_te`, `so_luong`, `sl`, `sl_thuc_te`, `ton_kho`, `quantity`, `qty`, …

Nếu header lạ, import cố fallback cột số / sheet 2 cột.

### Tồn hệ thống = 0?

- Kiểm tra `product_branches` đúng `branch_id` của phiếu  
- Phiếu chưa có chi nhánh → hệ thống fallback `user_branch()`

## Quyền liên quan

- `products.stock.index` / `create` / `view` / `manage` / `delete`
