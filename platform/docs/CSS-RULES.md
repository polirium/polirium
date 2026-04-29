# Polirium ERP - CSS Development Rules

> **QUAN TRỌNG**: Đọc và tuân thủ các rules này khi làm việc với CSS

---

## Cấu trúc thư mục

```
platform/core/ui/
├── resources/assets/
│   └── scss/
│       ├── app.scss                # Main entry point (compiled → app.min.css)
│       ├── config/                 # SCSS config files
│       ├── ui/                     # UI component SCSS
│       ├── polirium/               # Plain CSS/SCSS source files ✅
│       │   ├── design-tokens.scss  # Design tokens
│       │   ├── base-styles.scss    # Base styles
│       │   ├── professional-table.scss
│       │   ├── role-table.scss
│       │   ├── crm-users.scss
│       │   └── action-buttons.scss # Action buttons icon + text + màu sắc
│       └── polirium-core.scss      # Compiled → polirium-core.min.css
├── public/
│   ├── css/                        # Plain CSS files (copy + minify)
│   │   ├── brand-modal.css
│   │   ├── dashboard.css
│   │   ├── professional-detail-view.css
│   │   └── settings.css
│   └── js/                         # JS files
└── webpack.mix.js                  # Webpack configuration
```

**Dist files**: `public/vendor/polirium/core/ui/css/`

---

## Quy trình khi cần thêm CSS mới

### 1. Xác định loại CSS

| Loại | Vị trí | Build process | Load order |
|------|--------|--------------|------------|
| **SCSS** | `resources/assets/scss/` | Compile bởi webpack | Được khai báo trong `scssFiles` |
| **Polirium Styles** | `resources/assets/scss/polirium/` | Import vào `app.scss` | Load qua `app.min.css` |
| **Plain CSS** | `public/css/` | Copy + minify bởi webpack | Load qua `assets.php` |

### 2. Thêm Polirium Styles (Recommended)

Đối với các styles dùng chung trong toàn bộ ứng án (design tokens, base styles, components):

1. **Tạo file** trong `resources/assets/scss/polirium/`:
```bash
# Tạo file SCSS
touch platform/core/ui/resources/assets/scss/polirium/my-style.scss
```

2. **Import vào app.scss**:
```scss
// platform/core/ui/resources/assets/scss/app.scss
@import "./polirium/my-style.scss";
```

3. **Build CSS**:
```bash
npm run dev      # Development
npm run prod     # Production (minified)
```

4. **Xong!** Styles sẽ được load tự động qua `app.min.css`

### 3. Thêm Plain CSS (Optional)

Đối với các styles chỉ dùng ở một số trang cụ thể:

1. **Tạo file** trong `public/css/`:
```bash
touch platform/core/ui/public/css/my-style.css
```

2. **Thêm vào webpack.mix.js**:
```javascript
const plainCssFiles = [
    // ... existing files
    'my-style',
];
```

3. **Build**:
```bash
npm run dev
```

4. **Khai báo trong assets.php** (optional css):
```php
'optional' => [
    'css' => [
        'my-style' => 'core/ui/css/my-style.min.css',
    ],
],
```

5. **Load khi cần**:
```php
// In Livewire controller
Assets::loadCss('my-style');

// Or in Blade
@php load_css('my-style') @endphp
```

---

## Quy tắc quan trọng

### ✅ ĐƯỢC LÀM

1. **Polirium Styles** → Tạo trong `resources/assets/scss/polirium/`, import vào `app.scss`
2. **Plain CSS** → Tạo trong `public/css/`, thêm vào `plainCssFiles`
3. **Run build** → `npm run dev` hoặc `npm run prod`
4. **Load polirium styles** → Tự động qua `app.min.css`
5. **Load optional CSS** → Thêm vào `assets.php` optional css array
6. **Xóa file cũ** → Xóa các file CSS không cần thiết

### ❌ KHÔNG LÀM

1. ~~Tạo CSS trong `resources/css/`~~ → Dùng `scff/polirium/` hoặc `public/css/`
2. ~~Import plain CSS từ bên ngoài~~ → Import từ `polirium/` folder
3. ~~Load polirium styles trong Livewire~~ → Load tự động qua `app.min.css`
4. ~~Quên chạy `npm run dev`~~ → Build sau khi thay đổi
5. ~~Tạo file CSS vô tội vạ~~ → Theo quy trình webpack.mix.js

---

## Ví dụ thực tế

### Thêm Action Buttons CSS (Đã làm)

1. **Tạo file**: `platform/core/ui/resources/assets/scss/polirium/action-buttons.scss`

2. **Import vào app.scss**:
```scss
@import "./polirium/action-buttons.scss";
```

3. **Build**:
```bash
npm run dev
```

4. **Xong!** Sử dụng trong blade:
```blade
<div class="action-buttons-v2">
    <button class="action-btn-v2 edit">
        {!! tabler_icon('pencil') !!}
        <span class="badge-label">Sửa</span>
    </button>
</div>
```

---

## Troubleshooting

### CSS không load?

1. **Kiểm tra app.scss**: File đã được import chưa?
2. **Chạy build**: `npm run dev`
3. **Kiểm tra app.min.css**: File đã được tạo chưa?
4. **Kiểm tra assets.php**: `app` đã được khai báo chưa?

### Build errors?

1. **Kiểm tra syntax SCSS**: https://sass-lang.com/playground/
2. **Xóa cache**: `rm -rf node_modules/.cache`
3. **Rebuild**: `npm run dev`

### Path sai khi import?

- **SCSS import**: Dùng path tương đối từ file hiện tại
- **Polirium styles**: `@import "./polirium/file-name.scss";`

---

## Checklist

Khi thêm CSS mới, kiểm tra:

- [ ] File CSS đã tạo trong đúng thư mục (`polirium/` hoặc `public/css/`)
- [ ] Đã import vào `app.scss` (nếu là polirium style)
- [ ] Đã thêm vào `webpack.mix.js` (nếu là plain CSS)
- [ ] Đã chạy `npm run dev` hoặc `npm run prod`
- [ ] File minified đã được tạo trong `public/vendor/polirium/core/ui/css/`
- [ ] Test trên browser: F12 → Network → refresh → kiểm tra CSS file

---

## Files tham khảo

| File | Mô tả |
|------|--------|
| `webpack.mix.js` | Webpack configuration |
| `assets.php` | Assets config (load order) |
| `app.scss` | Main SCSS entry point |
| `resources/assets/scss/polirium/` | Polirium styles source |
| `public/css/` | Plain CSS source files |
| `public/vendor/polirium/core/ui/css/*.min.css` | Built CSS files |

---

**Last updated**: 2026-01-14
**Next review**: Khi thêm CSS mới
