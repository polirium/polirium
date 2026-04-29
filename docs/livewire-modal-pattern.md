# Livewire Modal Pattern - Documentation

> Tài liệu này tóm tắt cách tạo và sử dụng Modal component trong Polirium ERP project.

## 📋 Nội dung

- [Pattern chuẩn để tạo Modal](#pattern-chuẩn-để-tạo-modal)
- [View Structure](#view-structure)
- [Component Structure](#component-structure)
- [Event Dispatching](#event-dispatching)
- [Modal Placement](#modal-placement)
- [Common Mistakes](#common-mistakes)

---

## Pattern chuẩn để tạo Modal

### 1. View Structure

**Blade Template** (`resources/views/.../modal/xxx.blade.php`):

```blade
<div>
    <form wire:submit.prevent="save">
        <x-ui::modal
            id="modal-create-xxx"              {{-- Required: ID để trigger --}}
            :header="__('module::file.title')"
            class="modal-xl"
            {{-- KHÔNG dùng :show prop --}}
        >
            <x-ui::errors />

            {{-- Modal Body Content --}}
            <div class="row">
                {{-- Form fields here --}}
            </div>

            <x-slot:footer>
                <button type="button" data-bs-dismiss="modal">
                    {{ __('core/base::general.cancel') }}
                </button>
                <button type="submit" wire:loading.attr="disabled">
                    {{ __('core/base::general.save') }}
                </button>
            </x-slot:footer>
        </x-ui::modal>
    </form>
</div>
```

**QUAN TRỌNG:**
- ✅ Chỉ có `id` prop
- ❌ **KHÔNG** dùng `:show` prop
- ❌ **KHÔNG** dùng `x-data`, `x-show`
- Modal được trigger bằng JavaScript event, không phải Livewire prop

### 2. Component Structure

**Livewire Component** (`src/Http/Livewire/.../Modal/XxxComponent.php`):

```php
<?php

namespace Polirium\Modules\Module\Http\Livewire\Modal;

use Livewire\Attributes\On;
use Livewire\Component;

class ModalCreateXxxComponent extends Component
{
    public array $input = [];

    public function mount(): void
    {
        // Init data
    }

    // Listeners - register event handlers
    protected function getListeners(): array
    {
        return [
            'show-modal-create-xxx' => 'showModal',
        ];
    }

    // Event handler with #[On] attribute
    #[On('show-modal-create-xxx')]
    public function showModal(): void
    {
        $this->resetInputs();

        // Dispatch "modal" event với modal ID
        $this->dispatch("modal", "modal-create-xxx");
    }

    public function save(): void
    {
        $this->validate();

        // Save logic here...

        // Close modal
        $this->dispatch("modal", "modal-create-xxx", "hide");

        // Refresh parent component
        $this->dispatch('refresh-datatable');
    }

    public function render()
    {
        return view('modules/module::modal.modal-create-xxx');
    }
}
```

**KEY POINT:**
```php
$this->dispatch("modal", "modal-id");          // Mở modal
$this->dispatch("modal", "modal-id", "hide");    // Đóng modal
```

---

## Event Dispatching

### Mở Modal từ Button

**Cách 1: Dùng Alpine (khác Livewire component)**
```blade
<button @click="$dispatch('show-modal-create-xxx')">
```

**Cách 2: Dùng Livewire (trong Livewire component)**
```blade
<button wire:click="$dispatch('show-modal-create-xxx')">
```

### Modal Placement - QUAN TRỌNG

Modal component **PHẢI** được include ở chỗ mà event có thể dispatch đến.

**❌ SAI:**
```php
// PaymentFilterSidebarComponent
public function openModal() {
    $this->dispatch('show-modal-create-xxx');
}
```

```blade
{{-- filter-sidebar.blade.php --}}
@livewire('modules/accounting::payment.filter-sidebar')

<div class="d-grid gap-2">
    <button @click="$dispatch('show-modal-create-xxx')">...</button>
</div>
```

```blade
{{-- index.blade.php - KHÁC CONTEXT! --}}
@livewire('modules/accounting::payment.modal.modal-create-xxx')
```

**✅ ĐÚNG:**

Modal được include trong **cùng component scope** với button dispatch event:

**Option 1: Include trong footer của datatable**
```blade
{{-- resources/views/.../datatable/footer.blade.php --}}
@livewire('modules/accounting::payment.modal.modal-create-xxx')
```

**Option 2: Include trong main page view**
```blade
{{-- resources/views/.../index.blade.php --}}
@livewire('modules/accounting::payment.filter-sidebar')
@livewire('modules/accounting::payment.datatable.payment-table')

{{-- Modal ở đây - cùng level với datatable --}}
@livewire('modules/accounting::payment.modal.modal-create-xxx')
```

**Option 3: Include trong detail view**
```blade
{{-- resources/views/.../datatable/detail.blade.php --}}
{{-- Chi tiết row --}}

{{-- Modal được include ở cuối --}}
@livewire('modules/accounting::payment.modal.modal-create-xxx')
```

---

## Common Mistakes

### ❌ Sai: Dùng `:show` prop

```blade
<x-ui::modal :show="$showModal"> {{-- KHÔNG ĐƯỢC --}}
```

### ❌ Sai: Modal không được load

```blade
{{-- Detail view chỉ render khi expand row --}}
<details>
    @livewire('modules/...modal.modal-create-xxx') {{-- Không load từ đầu --}}
</details>
```

### ❌ Sai: Event scope khác

```blade
{{-- Component A dispatch event --}}
<div @click="$dispatch('show-modal')">

{{-- Component B - khác scope, không nhận được event --}}
@livewire('modules/other.modal')
```

### ❌ Sai: Dùng `x-data` với `entangle`

```blade
<div x-data="{ open: @entangle($showModal) }"> {{-- KHÔNG CẦN THIẾT --}}
    <x-ui::modal x-show="open">
```

---

## Checklist tạo Modal mới

1. [ ] Tạo Livewire Component với `showModal()` method
2. [ ] Thêm `getListeners()` trả về event mapping
3. [ ] Trong `showModal()`, dispatch `$this->dispatch("modal", "modal-id")`
4. [ ] Tạo blade view với `<x-ui::modal id="modal-id">` (KHÔNG `:show`)
5. [ ] Include modal component vào **đúng scope** với button dispatch
6. [ ] Register component trong `config/livewire.php`
7. [ ] Test bấm button → modal mở → save → modal đóng → datatable refresh

---

## Examples từ Codebase

### Modal đơn giản
- `modules/product/src/Http/Livewire/Payment/Modal/ModalCreateSaleChannelComponent.php`
- `modules/product/resources/views/payment/modal/modal-create-sale-channel.blade.php`

### Modal phức tạp với nhiều trường
- `modules/accounting/src/Http/Livewire/Index/Modal/ModalCreateAccountingComponent.php`
- `modules/accounting/resources/views/index/modal/modal-create-accounting.blade.php`

---

## File References

| File | Mô tả |
|------|--------|
| `config/livewire.php` | Register Livewire components |
| `resources/views/*/modal/*.blade.php` | Modal blade templates |
| `src/Http/Livewire/*/Modal/*Component.php` | Modal Livewire components |
