<p align="center">
    <img src="https://avatars.githubusercontent.com/u/149035793?s=200&v=4" width="100" alt="Polirium Logo">
</p>

<h1 align="center">Polirium ERP Platform</h1>

<p align="center">
    Nền tảng ERP modular được xây dựng trên Laravel framework
</p>

---

## Giới thiệu

**Polirium** là một nền tảng ERP (Enterprise Resource Planning) modular, được thiết kế để dễ dàng mở rộng và tùy biến. Hệ thống được xây dựng trên Laravel với kiến trúc module hóa, cho phép các doanh nghiệp tạo và tích hợp các module nghiệp vụ riêng.

### Tính năng chính

- **Modular Architecture** - Dễ dàng thêm/bớt modules
- **User & Role Management** - Quản lý người dùng và phân quyền
- **Multi-branch Support** - Hỗ trợ đa chi nhánh
- **Responsive Admin UI** - Giao diện quản trị hiện đại (Tabler + Livewire)
- **Multi-language** - Hỗ trợ đa ngôn ngữ
- **Media Manager** - Quản lý file và hình ảnh
- **Notification System** - Hệ thống thông báo mở rộng

## Cài đặt

### Cài đặt bằng giao diện (khuyến nghị)

Dành cho người dùng không chuyên kỹ thuật: sau khi mã nguồn và thư mục `vendor` đã được đưa lên máy chủ, mở địa chỉ ứng dụng bằng trình duyệt. Hệ thống sẽ tự chuyển đến `/install` và hướng dẫn lần lượt:

1. Kiểm tra phiên bản PHP, extension và quyền ghi thư mục.
2. Nhập và kiểm tra kết nối MySQL hoặc MariaDB.
3. Tạo cấu trúc dữ liệu, khám phá và kích hoạt các module.
4. Tạo tài khoản quản trị đầu tiên.

Trình cài đặt chỉ chấp nhận database trống để bảo vệ dữ liệu hiện có. Khi hoàn tất, hệ thống tạo file khóa trong `storage/installed`; muốn cài lại phải xóa database có chủ đích và xóa file khóa này.

### Cài đặt bằng command (dành cho developer)

Yêu cầu PHP 8.3+, Composer 2.10+ và Node.js 22.12+ (khuyến nghị Node 24 LTS).

```bash
# Clone repository with submodules
git clone --recursive https://github.com/polirium/polirium.git

# Or if you already cloned without --recursive
git submodule update --init --recursive

# Install dependencies
composer install
npm install
npm run production

# Setup environment
cp .env.example .env
php artisan key:generate

# Run Polirium installer (includes migration + setup steps)
php artisan poli:install

# Seed database (optional, if needed)
php artisan db:seed
```

## Tài liệu

Xem hướng dẫn chi tiết tại: **[platform/docs](./platform/docs/README.md)** · **[Wiki (draft)](./wiki/README.md)**

| Tài liệu                                                             | Mô tả                   |
| -------------------------------------------------------------------- | ----------------------- |
| [Wiki Home](./wiki/Home.md)                                          | Mục lục wiki            |
| [Tổng quan](./platform/docs/01-overview.md)                          | Kiến trúc hệ thống      |
| [Cấu trúc thư mục](./platform/docs/02-directory-structure.md)        | Cấu trúc project        |
| [Cách viết Module](./platform/docs/03-creating-modules.md)           | Hướng dẫn tạo module    |
| [Helper Functions](./platform/docs/04-helpers.md)                    | Các helper functions    |
| [Facades & Services](./platform/docs/05-facades-services.md)         | Facades và Services     |
| [Menu System](./platform/docs/06-menu-system.md)                     | Hệ thống menu           |
| [Permission System](./platform/docs/07-permission-system.md)         | Hệ thống phân quyền     |
| [Events & Notifications](./platform/docs/08-events-notifications.md) | Events và Notifications |
| [UI Components](./platform/docs/09-ui-components.md)                 | UI Components           |

## Tech Stack

- **Framework:** Laravel 13 (PHP 8.3+)
- **Frontend:** Livewire 4, Alpine.js, Tabler UI
- **Database:** MySQL/PostgreSQL
- **Cache:** Redis (optional)

## Cấu trúc Platform

```text
platform/
├── core/           # Core modules
│   ├── base/       # Base functionality
│   ├── ui/         # UI components
│   ├── media/      # Media management
│   └── support/    # Support classes
├── modules/        # Business modules
├── packages/       # Third-party packages
└── docs/           # Documentation
```

## Đăng nhập Admin

Mở `/login` và sử dụng tài khoản quản trị đã tạo trong trình cài đặt. Repo không cung cấp mật khẩu mặc định.

## Donate

Nếu thấy dự án hữu ích, bạn có thể ủng hộ qua các kênh sau:

1. **Momo:** `0369272718`
2. **TPBank:** `66605091991`
3. **ZaloPay:** `0369272718`
4. **PayPal:** `vingamagic@gmail.com`
5. **Buy Me a Coffee:** [buymeacoffee.com/nghianecom](https://buymeacoffee.com/nghianecom)

## License

The Polirium platform is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

---

<p align="center">
    Made with ❤️ by <a href="https://github.com/polirium">Polirium Team</a>
</p>
