# Polirium Wiki

> Nguồn nội dung wiki (định dạng GitHub Wiki). Đẩy lên GitHub Wiki theo hướng dẫn ở cuối [Home](Home.md).

## Trang

| File | Nội dung |
|------|----------|
| [Home.md](Home.md) | Trang chủ wiki |
| [_Sidebar.md](_Sidebar.md) | Menu bên trái GitHub Wiki |
| [Getting-Started.md](Getting-Started.md) | Cài đặt & chạy lần đầu |
| [Architecture.md](Architecture.md) | Kiến trúc platform |
| [Modules.md](Modules.md) | Module nghiệp vụ |
| [Permissions-and-Menu.md](Permissions-and-Menu.md) | Phân quyền & menu |
| [POS-and-Payments.md](POS-and-Payments.md) | POS, phương thức TT, VietQR |
| [Stock-Check.md](Stock-Check.md) | Kiểm kho & import Excel |
| [Coding-Standards.md](Coding-Standards.md) | Quy ước code |
| [Deployment.md](Deployment.md) | Deploy server |

## Đăng wiki lên GitHub

1. Mở repo → **Settings → General → Features → Wikis** → bật Wiki.
2. Tạo trang đầu trên UI (hoặc clone wiki sau khi bật):

```bash
git clone https://github.com/polirium/polirium.wiki.git
cp wiki/*.md polirium.wiki/
cd polirium.wiki
git add .
git commit -m "docs: initial Polirium wiki"
git push origin master
```

Wiki URL: https://github.com/polirium/polirium/wiki
