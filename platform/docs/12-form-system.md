# Hệ thống Form Components - Polirium ERP

> Hệ thống form components mới với UI/UX tối ưu theo chuẩn **Minimalism & Swiss Style**

---

## Tổng quan

Hệ thống form components mới được thiết kế để cung cấp trải nghiệm người dùng nhất quán, đẹp mắt và dễ sử dụng trên toàn bộ dự án Polirium ERP.

### Đặc điểm chính

- **Minimalism & Swiss Style**: Thiết kế sạch sẽ, khoảng trắng hợp lý, rõ ràng
- **Accessibility**: Đầy đủ labels, focus states, keyboard navigation
- **Responsive**: Hoạt động tốt trên mọi kích thước màn hình
- **Validation**: Hiển thị lỗi rõ ràng với icon
- **Loading States**: Feedback visual khi submit
- **Dark Mode Ready**: Tương thích với dark mode

---

## Components Reference

### 1. Input Component (`<x-ui.form::input>`)

Input field với label, icon, validation.

```blade
<x-ui.form::input
    wire:model="user.name"
    :label="__('Họ và tên')"
    :placeholder="__('Nhập họ và tên')"
    icon="user"
    required
/>
```

**Props:**

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `label` | string | null | Label text |
| `description` | string | null | Mô tả ngắn (hiển thị phía dưới label) |
| `placeholder` | string | null | Placeholder text |
| `hint` | string | null | Gợi ý (hiển thị dưới input) |
| `icon` | string | null | Tabler icon name |
| `type` | string | 'text' | Input type (text, email, tel, number, password, etc.) |
| `required` | boolean | false | Hiển thị dấu * |
| `disabled` | boolean | false | Disable input |
| `readonly` | boolean | false | Readonly input |
| `prepend` | slot | null | Content phía trước input |
| `append` | slot | null | Content phía sau input |

**Examples:**

```blade
{{-- Basic input --}}
<x-ui.form::input
    wire:model="customer.code"
    :label="__('Mã KH')"
    icon="hash"
/>

{{-- Email with validation --}}
<x-ui.form::input
    wire:model="customer.email"
    :label="__('Email')"
    type="email"
    required
    :hint="__('Chúng tôi sẽ không chia sẻ email của bạn')"
/>

{{-- Input with prepend/append --}}
<x-ui.form::input
    wire:model="product.price"
    :label="__('Giá bán')"
    type="number"
>
    <x-slot:prepend>₫</x-slot>
</x-ui.form::input>
```

---

### 2. Textarea Component (`<x-ui.form::textarea>`)

Textarea với resize control và character count.

```blade
<x-ui.form::textarea
    wire:model="product.description"
    :label="__('Mô tả')"
    :placeholder="__('Nhập mô tả sản phẩm')"
    :rows="4"
    :show-char-count="true"
    :maxlength="500"
/>
```

**Props:**

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `rows` | int | 4 | Số dòng hiển thị |
| `resize` | string | 'vertical' | resize direction (vertical, horizontal, both, none) |
| `maxlength` | int | null | Giới hạn ký tự |
| `showCharCount` | boolean | false | Hiển thị số ký tự |

---

### 3. Select Component (`<x-ui.form::select>`)

Custom select với search, multiple selection.

```blade
{{-- Single select --}}
<x-ui.form::select
    wire:model="customer.province_id"
    :label="__('Tỉnh/Thành phố')"
    :placeholder="__('Chọn tỉnh/thành phố')"
    :options="$provinces"
/>

{{-- Multiple select --}}
<x-ui.form::select
    wire:model="role_ids"
    :label="__('Vai trò')"
    :placeholder="__('Chọn vai trò')"
    :options="$roles"
    multiple
/>
```

**Props:**

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `options` | array | [] | Danh sách options |
| `placeholder` | string | null | Placeholder text |
| `multiple` | boolean | false | Cho phép chọn nhiều |
| `searchable` | boolean | true | Bật tìm kiếm |
| `icon` | string | null | Icon prefix |

---

### 4. Checkbox Component (`<x-ui.form::checkbox>`)

Checkbox với label, description, switch style.

```blade
{{-- Checkbox --}}
<x-ui.form::checkbox
    wire:model="user.agree_terms"
    :label="__('Tôi đồng ý với điều khoản')"
    required
/>

{{-- Switch --}}
<x-ui.form::checkbox
    wire:model="setting.enabled"
    :label="__('Bật tính năng')"
    :description="__('Khi bật, tính năng sẽ hoạt động')"
    :switch="true"
/>

{{-- Inline checkboxes --}}
<div class="d-flex gap-4">
    <x-ui.form::checkbox
        wire:model="customer.type"
        value="individual"
        :label="__('Cá nhân')"
        inline
    />
    <x-ui.form::checkbox
        wire:model="customer.type"
        value="company"
        :label="__('Công ty')"
        inline
    />
</div>
```

---

### 5. Radio Component (`<x-ui.form::radio>`)

Radio button với label, description.

```blade
<x-ui.form::radio
    wire:model="customer.sex"
    value="male"
    :label="__('Nam')"
    inline
/>
<x-ui.form::radio
    wire:model="customer.sex"
    value="female"
    :label="__('Nữ')"
    inline
/>
```

---

### 6. Group Component (`<x-ui.form::group>`)

Fieldset để nhóm các fields liên quan.

```blade
<x-ui.form::group
    :label="__('Thông tin cơ bản')"
    icon="user"
    :description="__('Nhập thông tin cá nhân của khách hàng')"
>
    <div class="col-12">
        <x-ui.form::input wire:model="customer.name" :label="__('Tên')" />
    </div>
    <div class="col-md-6">
        <x-ui.form::input wire:model="customer.phone" :label="__('Điện thoại')" />
    </div>
    <div class="col-md-6">
        <x-ui.form::input wire:model="customer.email" :label="__('Email')" />
    </div>
</x-ui.form::group>
```

**Props:**

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `label` | string | null | Tiêu đề group |
| `icon` | string | null | Tabler icon name |
| `description` | string | null | Mô tả group |
| `collapsible` | boolean | false | Có thể thu gọn |
| `collapsed` | boolean | false | Mặc định thu gọn |

---

### 7. Actions Component (`<x-ui.form::actions>`)

Container cho action buttons.

```blade
<x-ui.form::actions>
    <button type="button" class="btn btn-ghost-secondary" data-bs-dismiss="modal">
        {{ __('Hủy') }}
    </button>
    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
        <span wire:loading.remove wire:target="save">
            <i class="ti ti-device-floppy me-1"></i>
            {{ __('Lưu') }}
        </span>
        <span wire:loading wire:target="save">
            <i class="ti ti-loader-2 icon-spin me-1"></i>
            {{ __('Đang lưu...') }}
        </span>
    </button>
</x-ui.form::actions>
```

**Props:**

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `align` | string | 'right' | Vị trí buttons (left, center, right, justify) |

---

### 8. Tabs Component (`<x-ui.form::tabs>`)

Tab navigation cho form phức tạp.

```blade
<x-ui.form::tabs
    :tabs="[
        'info' => ['label' => 'Thông tin', 'icon' => 'info-circle'],
        'images' => ['label' => 'Hình ảnh', 'icon' => 'photo'],
        'seo' => ['label' => 'SEO', 'icon' => 'search'],
    ]"
    :active-tab="$activeTab"
>
    {{-- Tab content will be conditionally rendered based on activeTab --}}
</x-ui.form::tabs>
```

---

## Best Practices

### 1. Layout với Grid System

```blade
<div class="row g-4">
    <div class="col-lg-6">
        {{-- Left Column --}}
        <x-ui.form::group :label="__('Thông tin cá nhân')" icon="user">
            <div class="col-12">
                <x-ui.form::input wire:model="customer.name" :label="__('Tên')" />
            </div>
        </x-ui.form::group>
    </div>
    <div class="col-lg-6">
        {{-- Right Column --}}
        <x-ui.form::group :label="__('Thông tin liên hệ')" icon="mail">
            <div class="col-12">
                <x-ui.form::input wire:model="customer.email" :label="__('Email')" />
            </div>
        </x-ui.form::group>
    </div>
</div>
```

### 2. Validation Messages

```blade
{{-- Validation sẽ tự động hiển thị khi có lỗi --}}
<x-ui.form::input
    wire:model="user.email"
    :label="__('Email')"
    type="email"
    required
/>
{{-- Error message sẽ hiển thị với icon ✗ nếu có lỗi validation --}}
```

### 3. Loading States

```blade
<button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
    <span wire:loading.remove wire:target="save">
        <i class="ti ti-device-floppy"></i>
        {{ __('Lưu') }}
    </span>
    <span wire:loading wire:target="save">
        <i class="ti ti-loader-2 icon-spin"></i>
        {{ __('Đang lưu...') }}
    </span>
</button>
```

### 4. Conditional Fields

```blade
<x-ui.form::radio
    wire:model.live="customer.type"
    value="individual"
    :label="__('Cá nhân')"
/>

@if ($customer['type'] === 'company')
    <x-ui.form::input
        wire:model="customer.company_name"
        :label="__('Tên công ty')"
    />
@endif
```

---

## Translation Keys

Các translation keys cần có trong lang files:

```php
// general.php
'select_item' => 'Chọn...',
'select_items' => 'Chọn các mục...',
'search_placeholder' => 'Tìm kiếm...',
'no_results' => 'Không tìm thấy kết quả',
'leave_blank_to_keep' => 'Để trống nếu không muốn đổi',

// Các keys khác như save, saving, cancel, etc.
```

---

## Styling

Tất cả styles được tự động load qua `@once @push('styles')` directive. Không cần manually import CSS.

### Customization

Nếu muốn override styles, thêm vào theme CSS:

```css
/* Override input height */
.ui-form-control {
    min-height: 48px;
}

/* Custom focus color */
.ui-form-control:focus {
    border-color: #your-color;
}
```

---

## Migration từ Component Cũ

### Cũ (using `<x-form::input>`):

```blade
<x-form::input
    wire:model="customer.name"
    :label="__('Tên KH')"
/>
```

### Mới (using `<x-ui.form::input>`):

```blade
<x-ui.form::input
    wire:model="customer.name"
    :label="__('Tên KH')"
    :placeholder="__('Nhập tên khách hàng')"
    icon="user"
/>
```

**Lợi ích:**
- Icon support
- Better placeholder
- Improved UX
- Consistent spacing
- Better error display
- Accessibility improvements

---

## Examples

### 1. Simple Login Form

```blade
<form wire:submit.prevent="login">
    <x-ui.form::input
        wire:model="form.email"
        :label="__('Email')"
        type="email"
        icon="mail"
        required
    />

    <x-ui.form::input
        wire:model="form.password"
        :label="__('Mật khẩu')"
        type="password"
        icon="lock"
        required
    />

    <x-ui.form::actions>
        <button type="submit" class="btn btn-primary w-100">
            {{ __('Đăng nhập') }}
        </button>
    </x-ui.form::actions>
</form>
```

### 2. Customer Creation Form

```blade
<div class="row g-4">
    <div class="col-lg-6">
        <x-ui.form::group :label="__('Thông tin cơ bản')" icon="user">
            <div class="col-12">
                <x-ui.form::input
                    wire:model="customer.code"
                    :label="__('Mã KH')"
                    icon="hash"
                />
            </div>
            <div class="col-12">
                <x-ui.form::input
                    wire:model="customer.name"
                    :label="__('Tên KH')"
                    icon="user"
                    required
                />
            </div>
            <div class="col-md-6">
                <x-ui.form::input
                    wire:model="customer.phone"
                    :label="__('Điện thoại')"
                    icon="phone"
                    type="tel"
                />
            </div>
            <div class="col-md-6">
                <x-ui.form::input
                    wire:model="customer.birthday"
                    :label="__('Ngày sinh')"
                    type="date"
                />
            </div>
        </x-ui.form::group>
    </div>
    <div class="col-lg-6">
        <x-ui.form::group :label="__('Địa chỉ')" icon="map-pin">
            <div class="col-12">
                <x-ui.form::select
                    wire:model.live="customer.province_id"
                    :label="__('Tỉnh/Thành')"
                    :options="$provinces"
                />
            </div>
            <div class="col-12">
                <x-ui.form::textarea
                    wire:model="customer.address"
                    :label="__('Địa chỉ chi tiết')"
                    :rows="2"
                />
            </div>
        </x-ui.form::group>
    </div>
</div>
```

---

## Troubleshooting

### Component không render

Kiểm tra:
1. Component đã được đăng ký trong `config/components.php`
2. View file tồn tại trong `platform/core/ui/resources/views/components/form/`
3. Clear cache: `php artisan view:clear`

### Styles không load

- Kiểm tra có `@stack('styles')` trong layout
- Clear cache: `php artisan config:clear`

### Alpine.js không hoạt động

- Đảm bảo Alpine.js đã được load
- Kiểm tra console có lỗi gì không

---

## Roadmap

- [ ] Date/Time picker component
- [ ] Rich text editor component
- [ ] Autocomplete component
- [ ] File upload component
- [ ] Form validation rules integration
- [ ] Multi-step form wizard
