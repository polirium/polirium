# Polirium ERP - Webpack Build Guide

> **QUAN TRỌNG**: Luôn chạy build từ **root directory**, không chạy từ workspace

---

## Build Commands

### Development Mode
```bash
cd /Users/vingamagic/Developer/php/polirium
npm run dev
```

### Production Mode
```bash
cd /Users/vingamagic/Developer/php/polirium
npm run prod
```

---

## Cấu trúc Assets sau build

```
public/vendor/polirium/
├── core/
│   ├── ui/
│   │   ├── css/
│   │   │   ├── app.min.css                    # Main CSS (all polirium styles)
│   │   │   ├── polirium-core.min.css
│   │   │   ├── polirium-flags.min.css
│   │   │   └── ...
│   │   ├── js/
│   │   │   ├── polirium.min.js
│   │   │   ├── theme.min.js
│   │   │   ├── app.min.js
│   │   │   └── ...
│   │   └── libs/
│   └── media/
│       ├── css/
│       │   └── media-manager.css
│       └── js/
│           └── media-manager.js
└── modules/
    ├── accounting/
    │   └── js/
    │       └── accounting.min.js
    ├── print-forms/
    │   ├── css/
    │   │   └── editor.min.css
    │   └── js/
    │       └── editor.min.js
    └── product/
        └── js/
            └── product.min.js
```

---

## Webpack Mix Files

### Root Webpack Mix
`/Users/vingamagic/Developer/php/polirium/webpack.mix.js`

Tự động include tất cả webpack.mix.js từ:
- `platform/core/*/webpack.mix.js`
- `platform/modules/*/webpack.mix.js`

### Core UI Webpack Mix
`platform/core/ui/webpack.mix.js`
- Build CSS: polirium-core, polirium-flags, polirium-marketing, polirium-payments, polirium-props, polirium-social, polirium-themes, polirium-vendors, app
- Build JS: polirium, theme, app
- Copy libs directory

### Module Webpack Mix

#### Accounting
`platform/modules/accounting/webpack.mix.js`
- Build JS: accounting.min.js

#### Print Forms
`platform/modules/print-forms/webpack.mix.js`
- Build CSS: editor.min.css
- Build JS: editor.min.js

#### Product
`platform/modules/product/webpack.mix.js`
- Build JS: product.min.js

---

## Thêm Module Assets mới

### Bước 1: Tạo webpack.mix.js cho module

```javascript
// platform/modules/your-module/webpack.mix.js
let mix = require('laravel-mix');
let path = require('path');
let directory = path.basename(path.resolve(__dirname));

// Get the relative path from root to this directory
const rootPath = path.resolve(__dirname, '../../..');
const relativePath = path.relative(rootPath, __dirname);

// Path configuration
const source = relativePath;
const assets = source + '/resources/assets';
const publicPath = source + '/public';
const productFolder = 'public/vendor/polirium/modules/' + directory;

mix.disableNotifications();

// JS files
const jsFiles = [
    'your-script',
];

// Compile JS files
jsFiles.forEach(function (file) {
    mix.js(assets + '/js/' + file + '.js', productFolder + '/js/' + file + '.min.js');
});

// Copy built files back to public folder
mix.then(() => {
    const fs = require('fs');

    jsFiles.forEach(function (file) {
        const sourceFile = productFolder + '/js/' + file + '.min.js';
        const targetFile = publicPath + '/js/' + file + '.min.js';

        if (fs.existsSync(sourceFile)) {
            const targetDir = path.dirname(targetFile);
            if (!fs.existsSync(targetDir)) {
                fs.mkdirSync(targetDir, { recursive: true });
            }
            fs.copyFileSync(sourceFile, targetFile);
        }
    });
});
```

### Bước 2: Tạo assets file
```bash
# Tạo JS file
mkdir -p platform/modules/your-module/resources/assets/js
touch platform/modules/your-module/resources/assets/js/your-script.js
```

### Bước 3: Build
```bash
cd /Users/vingamagic/Developer/php/polirium
npm run prod
```

---

## Troubleshooting

### Lỗi path bị double
```
Error: ENOENT: no such file or directory, lstat '/path/to/project/platform/core/ui/platform/core/ui/...'
```

**Giải pháp**: Chạy build từ root directory, KHÔNG chạy từ workspace (platform/core/ui)

```bash
# ❌ SAI - Đừng chạy từ workspace
cd platform/core/ui
npm run prod

# ✅ ĐÚNG - Chạy từ root
cd /Users/vingamagic/Developer/php/polirium
npm run prod
```

### Assets không load?
1. Kiểm tra file tồn tại trong `public/vendor/polirium/`
2. Kiểm tra assets.php có khai báo chưa
3. Xóa cache: `php artisan cache:clear`

### Build errors?
```bash
# Xóa cache
rm -rf node_modules/.cache
rm -rf public/vendor/polirium

# Build lại
npm run prod
```

---

## File Paths trong Code

### Load CSS trong assets.php
```php
// Core CSS (load everywhere)
'css' => [
    'app' => 'core/ui/css/app.min.css',
    // ...
],

// Optional CSS (load when needed)
'optional' => [
    'css' => [
        'editor' => 'modules/print-forms/css/editor.min.css',
    ],
],
```

### Load CSS trong Livewire
```php
// Load core CSS (not needed - already loaded)
// Assets::loadCss('app'); // ❌ Not needed

// Load optional CSS
Assets::loadCss('editor');
```

### Load JS trong Blade
```blade
<!-- Core JS -->
<script src="{{ asset('vendor/polirium/core/ui/js/app.min.js') }}"></script>

<!-- Module JS -->
<script src="{{ asset('vendor/polirium/modules/accounting/js/accounting.min.js') }}"></script>
```

---

**Last updated**: 2026-01-14
