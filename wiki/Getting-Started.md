# Cài đặt

## Yêu cầu

- PHP 8.3+
- Composer 2
- Node.js (build assets)
- MySQL hoặc PostgreSQL
- Ext: `bcmath`, `mbstring`, `pdo`, `tokenizer`, `xml`, `gd` / `imagick` (media)

## Clone & submodule

```bash
git clone --recursive https://github.com/polirium/polirium.git
cd polirium

# Nếu đã clone thiếu submodule:
git submodule update --init --recursive
```

## Dependencies

```bash
composer install
npm install
```

## Environment

```bash
cp .env.example .env
php artisan key:generate
```

Cấu hình DB trong `.env` (`DB_*`).

## Cài Polirium

```bash
php artisan poli:install
# Tuỳ chọn:
php artisan db:seed
```

Build frontend (nếu cần):

```bash
npm run production
# hoặc yarn production
```

## Chạy local

```bash
php artisan serve
# Admin: http://127.0.0.1:8000/admin
```

Với Valet/Herd: trỏ domain (vd. `polirium.test`) vào `public/`.

## Path repositories

`composer.json` map local packages:

- `./platform/core` → `polirium/core`
- `./platform/modules/*`
- `./platform/packages/*`

Sau khi thêm module mới, chạy `composer dump-autoload` / `composer update`.
