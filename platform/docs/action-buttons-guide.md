# Action Buttons - Hướng dẫn sử dụng

## Overview

Action Buttons là hệ thống button chuyên nghiệp cho table rows với:
- **Tooltip** khi hover (desktop)
- **Icon + Text** trên mobile
- **Dropdown** cho nhiều actions
- **Responsive** & **Accessibility**

## Cách sử dụng

### 1. Load CSS

Trong blade view hoặc layout:

```blade
@php load_css('action-buttons') @endphp
```

Hoặc trong Livewire component `mount()`:

```php
\Polirium\Core\UI\Support\Assets::loadCss('action-buttons');
```

### 2. Basic Usage - Icon Only Buttons

```blade
<div class="action-buttons">
    <button
        class="action-btn edit icon-only"
        data-tooltip="Sửa"
        wire:click="edit({{ $row->id }})"
    >
        {!! tabler_icon('pencil', ['class' => 'icon']) !!}
    </button>

    <button
        class="action-btn delete icon-only"
        data-tooltip="Xóa"
        wire:click="delete({{ $row->id }})"
        wire:confirm="Bạn có chắc muốn xóa?"
    >
        {!! tabler_icon('trash', ['class' => 'icon']) !!}
    </button>
</div>
```

### 3. Icon + Text Buttons (Luôn hiện text)

```blade
<div class="action-buttons expanded">
    <button
        class="action-btn edit"
        wire:click="edit({{ $row->id }})"
    >
        {!! tabler_icon('pencil', ['class' => 'icon']) !!}
        <span class="action-text">Sửa</span>
    </button>

    <button
        class="action-btn delete"
        wire:click="delete({{ $row->id }})"
        wire:confirm="Bạn có chắc muốn xóa?"
    >
        {!! tabler_icon('trash', ['class' => 'icon']) !!}
        <span class="action-text">Xóa</span>
    </button>
</div>
```

### 4. Dropdown Menu (Cho nhiều actions)

```blade
<div class="action-dropdown" x-data="{ open: false }">
    {{-- Primary action - Edit button --}}
    <button
        class="action-btn edit icon-only"
        data-tooltip="Sửa"
        wire:click="edit({{ $row->id }})"
    >
        {!! tabler_icon('pencil', ['class' => 'icon']) !!}
    </button>

    {{-- More trigger }}
    <button
        class="action-btn more icon-only"
        data-tooltip="Thêm"
        @click="open = !open"
        aria-label="More actions"
    >
        {!! tabler_icon('dots', ['class' => 'icon']) !!}
    </button>

    {{-- Dropdown menu --}}
    <div class="action-dropdown-menu" :class="{ show: open }" @click.away="open = false">
        <button class="action-dropdown-item" wire:click="copy({{ $row->id }})">
            {!! tabler_icon('copy') !!}
            Copy sản phẩm
        </button>

        <div class="action-dropdown-divider"></div>

        <button class="action-dropdown-item" wire:click="delete({{ $row->id }})" wire:confirm="Xóa?">
            {!! tabler_icon('trash') !!}
            <span class="text-danger">Xóa sản phẩm</span>
        </button>
    </div>
</div>
```

### 5. Sử dụng Component

```blade
<x-ui::action-buttons
    :actions="[
        [
            'type' => 'edit',
            'icon' => 'pencil',
            'label' => 'Sửa',
            'action' => 'wire:click="edit(' . $row->id . ')"',
        ],
        [
            'type' => 'copy',
            'icon' => 'copy',
            'label' => 'Copy',
            'action' => 'wire:click="copy(' . $row->id . ')"',
        ],
        [
            'type' => 'delete',
            'icon' => 'trash',
            'label' => 'Xóa',
            'action' => 'wire:click="delete(' . $row->id . ')"',
            'confirm' => 'Bạn có chắc muốn xóa?',
        ],
    ]"
    :row="$row"
/>
```

## Button Types

| Type | Icon | Color | Usage |
|------|------|-------|-------|
| `edit` | pencil | Blue | Sửa/Chỉnh sửa |
| `delete` | trash | Red | Xóa |
| `view` | eye | Gray | Xem chi tiết |
| `copy` | copy | Indigo | Copy/Nhân bản |
| `active` | check | Green | Kích hoạt |
| `inactive` | x | Yellow | Vô hiệu hóa |
| `more` | dots | Gray | Menu dropdown |

## Utility Classes

### Compact Mode (Icon only)
```blade
<div class="action-buttons compact">
    <!-- Buttons will always be icon-only -->
</div>
```

### Expanded Mode (Icon + Text)
```blade
<div class="action-buttons expanded">
    <!-- Buttons will always show text -->
</div>
```

### Stack on Mobile
```blade
<div class="action-buttons stack-mobile">
    <!-- Buttons will stack vertically on mobile -->
</div>
```

### Size Variants
```blade
<button class="action-btn edit sm">Small</button>
<button class="action-btn edit">Medium (default)</button>
<button class="action-btn edit lg">Large</button>
```

## Responsive Behavior

| Breakpoint | Behavior |
|------------|----------|
| **Desktop (≥1024px)** | Icon-only với tooltip |
| **Tablet (768-1023px)** | Icon + text nếu có đủ space |
| **Mobile (<768px)** | Luôn hiện Icon + Text |
| **Small Mobile (<480px)** | Compact buttons |

## Accessibility

- **Keyboard Navigation**: Tab để focus, Enter để activate
- **ARIA Labels**: Tất cả buttons có `aria-label`
- **Focus Visible**: Outline khi focus
- **Reduced Motion**: Tự động disable animations
- **Screen Reader**: Tooltip text readable

## Dark Mode

CSS tự động hỗ trợ dark mode khi sử dụng `[data-bs-theme="dark"]`.

## Examples trong Project

### Customer Table Actions
File: `platform/modules/customer/resources/views/index/datatable/detail.blade.php`

```blade
<div class="action-buttons">
    <button class="action-btn edit icon-only"
        data-tooltip="{{ __('modules/customer::customer.edit') }}"
        wire:click="$dispatch('show-modal-edit-customer', { id: {{ $row->id }} })">
        {!! tabler_icon('pencil', ['class' => 'icon']) !!}
    </button>

    <button class="action-btn delete icon-only"
        data-tooltip="{{ __('modules/customer::customer.delete') }}"
        wire:click="$dispatch('triggerRemoveCustomer', { id: {{ $row->id }} })"
        wire:confirm="{{ __('modules/customer::customer.delete_confirm') }}">
        {!! tabler_icon('trash', ['class' => 'icon']) !!}
    </button>
</div>
```

### Product Table Actions (Với Dropdown)
File: `platform/modules/product/resources/views/index/datatable/detail.blade.php`

```blade
<div class="action-dropdown" x-data="{ open: false }">
    <button class="action-btn edit icon-only"
        data-tooltip="Sửa"
        wire:click="$dispatch('show-modal-create-product', { id: {{ $id }} })">
        {!! tabler_icon('pencil', ['class' => 'icon']) !!}
    </button>

    <button class="action-btn more icon-only"
        data-tooltip="Thêm"
        @click="open = !open">
        {!! tabler_icon('dots', ['class' => 'icon']) !!}
    </button>

    <div class="action-dropdown-menu" :class="{ show: open }" @click.away="open = false">
        <button class="action-dropdown-item"
            wire:click="$dispatch('triggerCopyProduct', { id: {{ $id }} })">
            {!! tabler_icon('copy') !!}
            Copy sản phẩm
        </button>

        <div class="action-dropdown-divider"></div>

        <button class="action-dropdown-item text-danger"
            wire:click="$dispatch('triggerRemoveProduct', { id: {{ $id }} })"
            wire:confirm="Are you sure?">
            {!! tabler_icon('trash') !!}
            Xóa sản phẩm
        </button>
    </div>
</div>
```

## Troubleshooting

### Tooltip không hiện
- Kiểm tra CSS đã load: `load_css('action-buttons')`
- Kiểm tra `data-tooltip` attribute có đúng không

### Mobile vẫn hiện icon-only
- Xóa class `compact` hoặc `icon-only`
- Thêm class `expanded` để force hiện text

### Dropdown không hoạt động
- Kiểm tra Alpine.js đã load chưa
- Kiểm tra `x-data="{ open: false }"` trên container
- Kiểm tra `@click.away="open = false"` để close khi click outside

## Migration từ Buttons cũ

### Before (Sửa):
```blade
<x-ui.button
    type="button"
    wire:click="edit({{ $id }})"
    color="warning"
    size="sm"
    icon="pencil"
    label="Sửa"
/>
```

### After (Action Button):
```blade
<button
    class="action-btn edit icon-only"
    data-tooltip="Sửa"
    wire:click="edit({{ $id }})"
>
    {!! tabler_icon('pencil', ['class' => 'icon']) !!}
    <span class="action-text">Sửa</span>
</button>
```

## Checklist trước khi deploy

- [ ] CSS đã load trong layout
- [ ] Alpine.js đã enabled
- [ ] Test trên desktop (tooltip)
- [ ] Test trên mobile (icon + text)
- [ ] Test keyboard navigation (Tab, Enter)
- [ ] Test dark mode
- [ ] Test với screen reader
