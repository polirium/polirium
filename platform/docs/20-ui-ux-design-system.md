# Polirium ERP - UI/UX Design System

> Version: 1.0
> Last Updated: 2026-01-13
> Reference: `/admin/brands` implementation

---

## Table of Contents

1. [Design Philosophy](#design-philosophy)
2. [Color System](#color-system)
3. [Typography](#typography)
4. [Spacing & Layout](#spacing--layout)
5. [Components](#components)
6. [Page Patterns](#page-patterns)
7. [Implementation Guide](#implementation-guide)

---

## Design Philosophy

### Core Principles

1. **Professional & Modern**
   - Clean layouts with generous whitespace
   - Subtle gradients and shadows for depth
   - Consistent visual language across all modules

2. **User-Centric**
   - Clear visual hierarchy
   - Intuitive navigation patterns
   - Immediate feedback for all interactions

3. **Accessible**
   - WCAG 2.1 AA compliant contrast ratios
   - Keyboard navigation support
   - Screen reader friendly markup

4. **Responsive**
   - Mobile-first approach
   - Breakpoints: 640px, 768px, 1024px, 1280px
   - Touch-friendly targets (min 44x44px)

---

## Color System

### Primary Colors

```css
--prof-primary: #2563EB;        /* Blue 600 */
--prof-primary-hover: #1D4ED8;  /* Blue 700 */
--prof-primary-light: #EFF6FF;   /* Blue 50 */
--prof-primary-bg: #DBEAFE;      /* Blue 100 */
```

### Semantic Colors

```css
/* Success */
--prof-success: #10B981;
--prof-success-bg: #D1FAE5;
--prof-success-light: #ECFDF5;

/* Warning */
--prof-warning: #F59E0B;
--prof-warning-bg: #FED7AA;
--prof-warning-light: #FEF3C7;

/* Danger */
--prof-danger: #EF4444;
--prof-danger-bg: #FECACA;
--prof-danger-light: #FEF2F2;

/* Info */
--prof-info: #3B82F6;
--prof-info-bg: #BFDBFE;
--prof-info-light: #EFF6FF;
```

### Neutral Colors

```css
--prof-text-primary: #1E293B;    /* Slate 800 */
--prof-text-secondary: #64748B;  /* Slate 500 */
--prof-text-muted: #94A3B8;       /* Slate 400 */

--prof-bg-primary: #FFFFFF;       /* White */
--prof-bg-secondary: #F8FAFC;     /* Slate 50 */
--prof-bg-tertiary: #F1F5F9;      /* Slate 100 */

--prof-border-light: #F1F5F9;     /* Slate 100 */
--prof-border-medium: #E2E8F0;    /* Slate 200 */
--prof-border-dark: #CBD5E1;      /* Slate 300 */
```

### Gradients

```css
/* Primary Gradient */
--prof-gradient-primary: linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 100%);

/* Hero Gradient */
--prof-gradient-hero: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);

/* Surface Gradient */
--prof-gradient-surface: linear-gradient(180deg, #FFFFFF 0%, #F8FAFC 100%);
```

---

## Typography

### Font Families

```css
--font-sans: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
--font-mono: 'JetBrains Mono', 'Fira Code', monospace;
```

### Type Scale

```css
/* Headings */
--text-h1: 2rem;      /* 32px */
--text-h2: 1.5rem;    /* 24px */
--text-h3: 1.25rem;   /* 20px */
--text-h4: 1.125rem;  /* 18px */
--text-h5: 1rem;      /* 16px */
--text-h6: 0.875rem;  /* 14px */

/* Body */
--text-lg: 1rem;      /* 16px */
--text-base: 0.875rem; /* 14px */
--text-sm: 0.75rem;   /* 12px */
--text-xs: 0.625rem;  /* 10px */
```

### Font Weights

```css
--font-light: 300;
--font-normal: 400;
--font-medium: 500;
--font-semibold: 600;
--font-bold: 700;
```

### Letter Spacing

```css
--tracking-tight: -0.025em;
--tracking-normal: -0.01em;
--tracking-wide: 0.025em;
```

---

## Spacing & Layout

### Spacing Scale

```css
--space-1: 0.25rem;  /* 4px */
--space-2: 0.5rem;   /* 8px */
--space-3: 0.75rem;  /* 12px */
--space-4: 1rem;     /* 16px */
--space-5: 1.25rem;  /* 20px */
--space-6: 1.5rem;   /* 24px */
--space-8: 2rem;     /* 32px */
--space-10: 2.5rem;  /* 40px */
--space-12: 3rem;    /* 48px */
```

### Border Radius

```css
--radius-sm: 6px;
--radius-md: 8px;
--radius-lg: 12px;
--radius-xl: 16px;
--radius-full: 9999px;
```

### Shadows

```css
--shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
--shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
--shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
--shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);

/* Colored Shadows */
--shadow-primary: 0 4px 14px rgba(37, 99, 235, 0.15);
--shadow-success: 0 4px 14px rgba(16, 185, 129, 0.15);
```

### Container Widths

```css
--container-sm: 640px;
--container-md: 768px;
--container-lg: 1024px;
--container-xl: 1280px;
--container-2xl: 1536px;
```

---

## Components

### 1. Professional Card Header

**Usage:** Main page headers with icon, title, badge, and actions

```blade
<div class="professional-card-header">
    <!-- Left: Icon + Title + Badge -->
    <div class="d-flex align-items-center gap-3">
        <div class="professional-header-icon-wrapper">
            <svg class="professional-header-icon" width="22" height="22" viewBox="0 0 24 24">
                <!-- Icon path -->
            </svg>
        </div>

        <h3 class="professional-card-title">{{ $title }}</h3>

        <span class="professional-badge neutral">
            <span class="professional-badge-dot"></span>
            <span x-text="count + ' records'"></span>
        </span>
    </div>

    <!-- Right: Actions -->
    <div class="professional-card-actions">
        <button class="professional-btn-action primary">
            <svg><!-- Icon --></svg>
            Action
        </button>
    </div>
</div>
```

**CSS:**
```css
.professional-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--prof-border-light);
}

.professional-header-icon-wrapper {
    width: 42px;
    height: 42px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--prof-gradient-primary);
    border-radius: var(--radius-md);
}

.professional-card-title {
    font-size: var(--text-h4);
    font-weight: var(--font-semibold);
    letter-spacing: var(--tracking-normal);
    color: var(--prof-text-primary);
}

.professional-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: var(--radius-full);
    font-size: var(--text-sm);
    font-weight: var(--font-medium);
}

.professional-badge-dot {
    width: 6px;
    height: 6px;
    border-radius: var(--radius-full);
    background: var(--prof-primary);
}
```

---

### 2. Professional Action Button

**Variants:** `primary`, `secondary`, `success`, `warning`, `danger`

```blade
<button class="professional-btn-action {{ $variant }}">
    @if($icon)
        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <!-- Icon path -->
        </svg>
    @endif
    {{ $label }}
</button>
```

**CSS:**
```css
.professional-btn-action {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 0.5rem 1rem;
    font-size: var(--text-sm);
    font-weight: var(--font-medium);
    border-radius: var(--radius-md);
    border: none;
    cursor: pointer;
    transition: all var(--prof-transition-normal);
}

.professional-btn-action.primary {
    background: var(--prof-primary);
    color: white;
}

.professional-btn-action.primary:hover {
    background: var(--prof-primary-hover);
    transform: translateY(-1px);
    box-shadow: var(--shadow-primary);
}

.professional-btn-action.secondary {
    background: white;
    color: var(--prof-text-primary);
    border: 1px solid var(--prof-border-medium);
}

.professional-btn-action.secondary:hover {
    background: var(--prof-bg-secondary);
}
```

---

### 3. Professional Modal

**Structure:**

```blade
<x-ui.modal id="modal-{{ $entity }}" :header="$title" size="modal-lg">
    <div class="professional-modal-wrapper">
        <!-- Hero Section -->
        <div class="professional-modal-hero">
            <div class="professional-modal-icon-box">
                <svg><!-- Icon --></svg>
            </div>
            <div class="professional-modal-content">
                <h3>{{ $heading }}</h3>
                <p>{{ $subheading }}</p>
            </div>
        </div>

        <!-- Form Section -->
        <div class="professional-modal-body">
            <!-- Form fields here -->
        </div>
    </div>

    <x-slot name="footer">
        <div class="professional-modal-footer">
            <button type="button" class="professional-btn-action secondary"
                    data-bs-dismiss="modal">
                {{ __('Cancel') }}
            </button>
            <button type="submit" class="professional-btn-action primary"
                    wire:loading.attr="disabled">
                <svg x-show="!$loading" class="d-none"><!-- Icon --></svg>
                <svg x-show="$loading" class="spinner"><!-- Spinner --></svg>
                {{ $submitLabel }}
            </button>
        </div>
    </x-slot>
</x-ui.modal>
```

**CSS:**
```css
.professional-modal-wrapper {
    padding: 0;
}

.professional-modal-hero {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.5rem;
    background: var(--prof-gradient-hero);
    border-bottom: 1px solid var(--prof-border-light);
}

.professional-modal-icon-box {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-md);
}

.professional-modal-content h3 {
    font-size: var(--text-h4);
    font-weight: var(--font-semibold);
    color: var(--prof-text-primary);
    margin-bottom: 0.25rem;
}

.professional-modal-content p {
    font-size: var(--text-sm);
    color: var(--prof-text-secondary);
    margin-bottom: 0;
}

.professional-modal-body {
    padding: 1.5rem;
}

.professional-modal-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.75rem;
    padding: 1rem 1.5rem;
    background: var(--prof-bg-secondary);
    border-top: 1px solid var(--prof-border-light);
}
```

---

### 4. Professional Form Input

**Features:** Icon, label, required/optional marks, validation states

```blade
<div class="professional-form-group">
    <label class="professional-form-label" for="{{ $id }}">
        @if($icon)
            <svg class="professional-form-icon" width="16" height="16">
                <!-- Icon path -->
            </svg>
        @endif
        {{ $label }}
        @if($required)
            <span class="professional-mark required">*</span>
        @else
            <span class="professional-mark optional">Optional</span>
        @endif
    </label>

    <div class="professional-input-wrapper">
        @if($icon)
            <div class="professional-input-icon-left">
                <svg width="18" height="18" fill="none" stroke="currentColor">
                    <!-- Icon path -->
                </svg>
            </div>
        @endif

        <input type="text"
               id="{{ $id }}"
               class="professional-form-input {{ $icon ? 'has-icon' : '' }}"
               value="{{ $value }}"
               placeholder="{{ $placeholder }}">
    </div>

    @error($field)
        <div class="professional-form-error">
            <span class="professional-error-dot"></span>
            {{ $message }}
        </div>
    @enderror
</div>
```

**CSS:**
```css
.professional-form-group {
    margin-bottom: 1.25rem;
}

.professional-form-label {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 0.5rem;
    font-size: var(--text-sm);
    font-weight: var(--font-medium);
    color: var(--prof-text-primary);
}

.professional-form-icon {
    color: var(--prof-primary);
}

.professional-mark {
    font-size: var(--text-xs);
    font-weight: var(--font-normal);
}

.professional-mark.required {
    color: var(--prof-danger);
}

.professional-mark.optional {
    color: var(--prof-text-muted);
}

.professional-input-wrapper {
    position: relative;
}

.professional-form-input {
    width: 100%;
    padding: 0.625rem 0.875rem;
    font-size: var(--text-base);
    color: var(--prof-text-primary);
    background: white;
    border: 1px solid var(--prof-border-medium);
    border-radius: var(--radius-md);
    transition: all var(--prof-transition-fast);
}

.professional-form-input.has-icon {
    padding-left: 2.25rem;
}

.professional-form-input:focus {
    outline: none;
    border-color: var(--prof-primary);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.professional-input-icon-left {
    position: absolute;
    left: 0.875rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--prof-text-muted);
}

.professional-form-error {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 0.5rem;
    font-size: var(--text-sm);
    color: var(--prof-danger);
}

.professional-error-dot {
    width: 4px;
    height: 4px;
    border-radius: var(--radius-full);
    background: var(--prof-danger);
}
```

---

## Page Patterns

### Standard Index Page Layout

```blade
<x-ui.layouts.app>
    @section('content')
    <div class="page-body">
        <div class="container-xl">
            <div class="row row-cards g-4">
                <!-- Sidebar (optional) -->
                <div class="col-lg-3">
                    <x-ui.card class="card-sticky">
                        <!-- Filters or search sidebar -->
                    </x-ui.card>
                </div>

                <!-- Main Content -->
                <div class="col-lg-9">
                    <x-ui.card>
                        @livewire('{{ $module }}-table')
                    </x-ui.card>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals loaded at bottom -->
    @livewire('{{ $module }}.modal.modal-create-{{ $entity }}')
    @endsection
</x-ui.layouts.app>
```

---

## Implementation Guide

### Step 1: Create Professional Header

Create `platform/modules/{module}/resources/views/{entity}/datatable/header.blade.php`:

```blade
<div class="professional-card-header">
    <div class="d-flex align-items-center gap-3">
        {{-- Icon Wrapper --}}
        <div class="professional-header-icon-wrapper">
            <svg class="professional-header-icon" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconPath }}" />
            </svg>
        </div>

        {{-- Title --}}
        <h3 class="professional-card-title">{{ $title }}</h3>

        {{-- Record Count Badge --}}
        <span class="professional-badge neutral">
            <span class="professional-badge-dot"></span>
            <span x-text="window.$event '{{ $tableName }}-table:dataUpdated''].detail.count + ' ' + '{{ $title }}'"></span>
        </span>
    </div>

    {{-- Action Buttons --}}
    <div class="professional-card-actions">
        <button class="professional-btn-action primary"
                wire:click="$emit('showModalCreate{{ $entity }}')">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Add {{ $entity }}
        </button>
    </div>
</div>

@push('styles')
<link href="{{ asset('vendor/polirium/core/ui/css/professional-table.css') }}" rel="stylesheet">
@endpush
```

### Step 2: Create Professional Modal

Create `platform/modules/{module}/resources/views/{entity}/modal/modal-create-{entity}.blade.php`:

```blade
<x-ui.modal id="modal-create-{{ $entity }}"
          :header="$header ?? 'Create ' . $entity"
          size="modal-lg">
    <div class="professional-modal-wrapper">
        {{-- Hero Section --}}
        <div class="professional-modal-hero">
            <div class="professional-modal-icon-box">
                <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconPath }}" />
                </svg>
            </div>
            <div class="professional-modal-content">
                <h3>{{ $heading }}</h3>
                <p>{{ $subheading }}</p>
            </div>
        </div>

        {{-- Form Section --}}
        <div class="professional-modal-body">
            <form wire:submit.prevent="save">
                {{-- Your form fields here --}}
            </form>
        </div>
    </div>

    <x.slot name="footer">
        <div class="professional-modal-footer">
            <button type="button" class="professional-btn-action secondary" data-bs-dismiss="modal">
                {{ __('Cancel') }}
            </button>
            <button type="submit" class="professional-btn-action primary" wire:loading.attr="disabled">
                <svg x-show="!$loading" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <svg x-show="$loading" class="spinner" width="16" height="16" viewBox="0 0 24 24">
                    <circle class="spinner-path" cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="3"></circle>
                </svg>
                {{ $submitLabel ?? 'Save' }}
            </button>
        </div>
    </x.slot>
</x-ui.modal>

@push('styles')
<style>
    .spinner-path {
        stroke-dasharray: 60;
        stroke-dashoffset: 0;
        animation: spinner-rotate 1.5s linear infinite;
    }

    @keyframes spinner-rotate {
        to { stroke-dashoffset: -120; }
    }

    .spinner {
        animation: spinner-spin 0.8s linear infinite;
    }

    @keyframes spinner-spin {
        to { transform: rotate(360deg); }
    }
</style>
@endpush
```

### Step 3: Add Professional CSS

Create or update `platform/core/ui/resources/css/professional-table.css`:

```css
:root {
    /* Primary */
    --prof-primary: #2563EB;
    --prof-primary-hover: #1D4ED8;
    --prof-primary-light: #EFF6FF;
    --prof-primary-bg: #DBEAFE;

    /* Text */
    --prof-text-primary: #1E293B;
    --prof-text-secondary: #64748B;
    --prof-text-muted: #94A3B8;

    /* Background */
    --prof-bg-primary: #FFFFFF;
    --prof-bg-secondary: #F8FAFC;
    --prof-bg-tertiary: #F1F5F9;

    /* Border */
    --prof-border-light: #F1F5F9;
    --prof-border-medium: #E2E8F0;

    /* Spacing */
    --prof-radius-sm: 6px;
    --prof-radius-md: 8px;
    --prof-radius-lg: 12px;

    /* Transitions */
    --prof-transition-fast: 150ms ease;
    --prof-transition-normal: 200ms ease;
}

/* Dark mode */
[data-bs-theme="dark"] {
    --prof-text-primary: #F1F5F9;
    --prof-text-secondary: #94A3B8;
    --prof-text-muted: #64748B;

    --prof-bg-primary: #1E293B;
    --prof-bg-secondary: #0F172A;
    --prof-bg-tertiary: #334155;

    --prof-border-light: #334155;
    --prof-border-medium: #475569;
}

/* ... Rest of the CSS from Components section ... */
```

### Step 4: Update Livewire Table Component

Update `setUp()` method in your Table component:

```php
public function setUp(): array
{
    return [
        (new Header())
            ->includeViewOnTop('modules.{module}.{entity}.datatable.header')
            ->showSearchInput(),
        (new Footer())
            ->showPerPage($this->perPage)
            ->showRecordCount()
            ->pagination($this->pagination),
    ];
}
```

---

## Module Checklist

Use this checklist when implementing new modules or refactoring existing ones:

### Page Structure
- [ ] Uses `col-lg-3/col-lg-9` grid split (with sidebar) or `col-12` (without)
- [ ] Wrapped in `<x-ui.card>`
- [ ] Livewire table component loaded
- [ ] Modals loaded at bottom of page

### Header
- [ ] Professional header with icon wrapper
- [ ] Title with proper typography
- [ ] Record count badge with dot indicator
- [ ] Action buttons with hover states
- [ ] Professional CSS linked

### Modal
- [ ] Hero section with icon + heading
- [ ] Form with professional styling
- [ ] Required/optional marks on labels
- [ ] Icon in input fields (if applicable)
- [ ] Footer with Cancel + Save buttons
- [ ] Loading states with spinner

### CSS
- [ ] Uses CSS variables for colors
- [ ] Proper spacing with space scale
- [ ] Border radius consistent
- [ ] Transitions on interactive elements
- [ ] Dark mode support

### Accessibility
- [ ] Proper heading hierarchy
- [ ] ARIA labels where needed
- [ ] Keyboard navigation support
- [ ] Focus indicators visible
- [ ] Color contrast meets WCAG AA

---

## Migration Guide

### Existing Module → Professional Style

**Before (Standard Header):**
```blade
<div class="card-header">
    <h3 class="card-title">{{ $title }}</h3>
    <div class="card-actions">
        <button class="btn btn-success">
            {{ tabler_icon('plus') }}
            Create
        </button>
    </div>
</div>
```

**After (Professional Header):**
```blade
<div class="professional-card-header">
    <div class="d-flex align-items-center gap-3">
        <div class="professional-header-icon-wrapper">
            <svg><!-- Icon --></svg>
        </div>
        <h3 class="professional-card-title">{{ $title }}</h3>
        <span class="professional-badge neutral">
            <span class="professional-badge-dot"></span>
            <span x-text="count + ' records'"></span>
        </span>
    </div>
    <div class="professional-card-actions">
        <button class="professional-btn-action primary">
            <svg><!-- Plus icon --></svg>
            Create
        </button>
    </div>
</div>
```

---

## Resources

### Icon Library
- [Tabler Icons](https://tabler-icons.io/) - Primary icon set
- [Heroicons](https://heroicons.com/) - Secondary icon set

### Color Reference
- [Tailwind CSS Colors](https://tailwindcss.com/docs/customizing-colors)
- [Sass Color Scale](https://davidandsuzi.com/sass-color-scale/)

### Accessibility
- [WCAG 2.1 Guidelines](https://www.w3.org/WAI/WCAG21/quickref/)
- [WebAIM Contrast Checker](https://webaim.org/resources/contrastchecker/)

---

## Changelog

### v1.0 (2026-01-13)
- Initial release
- Documented brands page pattern
- Created component library
- Added implementation guide
