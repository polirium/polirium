# Deploy server

## Checklist thư mục ghi được

Laravel cần các thư mục sau **tồn tại và writable** bởi user PHP:

```bash
cd /path/to/public_html   # hoặc root project

mkdir -p bootstrap/cache
mkdir -p storage/framework/{cache,sessions,views}
mkdir -p storage/logs

chmod -R 775 bootstrap/cache storage
# chown theo user hosting / www-data nếu cần
```

### Lỗi thường gặp

```text
The bootstrap/cache directory must be present and writable.
Script @php artisan package:discover ... returned with error code
```

→ Tạo `bootstrap/cache` + `chmod 775` (hoặc `777` tạm trên shared hosting), rồi chạy lại:

```bash
composer install
# hoặc
composer dump-autoload
php artisan package:discover
```

## Sau deploy

```bash
composer install --no-dev -o
php artisan migrate --force
php artisan optimize:clear
# php artisan config:cache
# php artisan route:cache
# php artisan view:cache
```

## Submodule

```bash
git submodule update --init --recursive
```

## Public path

Document root phải trỏ vào `public/` (không trỏ root project).

## VietQR / mạng ngoài

Server cần cho phép HTTPS tới `vietqr.app` / CDN ảnh QR nếu firewall chặn outbound.
