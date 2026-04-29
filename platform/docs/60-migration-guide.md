# UI/UX Migration Guide

## Overview

This guide helps migrate from old UI patterns to the new design system.

---

## Phase 1: Inline Styles → External CSS

### Before (Inline Styles)

```blade
@once
@push('styles')
<style>
    .my-component {
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 0.5rem;
    }
</style>
@endpush
@endonce

<div class="my-component">
    Content
</div>
```

### After (Design Tokens)

```blade
{{-- In blade file --}}
<div class="my-component">
    Content
</div>

{{-- In external CSS file --}}
/* design-tokens.css */
:root {
    --space-4: 1rem;
    --radius-lg: 0.5rem;
    --prof-bg-secondary: #f8f9fa;
}

/* my-component.css */
.my-component {
    padding: var(--space-4);
    background: var(--prof-bg-secondary);
    border-radius: var(--radius-lg);
}
```

---

## Phase 2: Custom Components → x-ui Components

### Before (Custom Code)

```blade
<div class="card">
    <div class="card-header">
        <h3>Card Title</h3>
        <button type="button" class="btn-close" data-bs-dismiss="modal">×</button>
    </div>
    <div class="card-body">
        Card content
    </div>
</div>
```

### After (x-ui Component)

```blade
<x-ui::card>
    <x-slot:header>
        <h3 class="card-title">Card Title</h3>
    </x-slot:header>
    <x-slot:body>
        Card content
    </x-slot:body>
</x-ui::card>
```

---

## Phase 3: Form Refactoring

### Before (Old Form Pattern)

```blade
<div class="form-group">
    <label>Full Name</label>
    <input type="text" name="full_name" class="form-control" placeholder="Enter your full name">
    @if($errors->has('full_name'))
        <div class="text-danger">{{ $errors->first('full_name') }}</div>
    @endif
</div>
```

### After (New Form Components)

```blade
<div class="mb-3">
    <x-ui.form::label for="full_name" required>
        Full Name
    </x-ui.form::label>

    <x-ui.form::input
        name="full_name"
        type="text"
        placeholder="Enter your full name"
        required
    />

    <x-ui.form::error :attribute="'full_name'" />
</div>
```

---

## Phase 4: Modal Refactoring

### Before (Inline Modal)

```blade
<div class="modal fade" id="exampleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modal Title</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Modal body text goes here.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary">Save changes</button>
            </div>
        </div>
    </div>
</div>
```

### After (x-ui Component)

```blade
<x-ui::modal id="modal-create-user" size="modal-lg">
    <x-slot:title>
        <h5 class="modal-title">Create New User</h5>
    </x-slot:title>

    <x-slot:body>
        <form wire:submit.prevent="save">
            @include('users.form-fields')
        </form>
    </x-slot:body>

    <x-slot:footer>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            Cancel
        </button>
        <button type="submit" class="btn btn-primary" wire:click="save">
            Create User
        </button>
    </x-slot:footer>
</x-ui::modal>
```

---

## Phase 5: Alert Refactoring

### Before (Custom Alert)

```blade
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
```

### After (x-ui Component)

```blade
@if(session('success'))
    <x-ui::alert variant="success" dismissible>
        {{ session('success') }}
    </x-ui::alert>
@endif
```

---

## Phase 6: Empty State Refactoring

### Before (No Visual Feedback)

```blade
@if($users->count() === 0)
    <p>No users found.</p>
@endif
```

### After (Empty State Component)

```blade
@if($users->count() === 0)
    <x-ui::empty-state
        :icon="tabler_icon('users', ['class' => 'icon'])"
        :title="__('No users found')"
        :description="__('Get started by creating your first user account.')"
        :actionText="'Create User'"
        :actionUrl="'route('users.create')'"
    />
@endif
```

---

## Phase 7: Skeleton Loading

### Before (Loading Spinner)

```blade
@if($loading)
    <div class="text-center py-5">
        <div class="spinner-border" role="status"></div>
    </div>
@endif
```

### After (Skeleton Component)

```blade
@if($loading)
    <div class="skeleton-grid">
        @foreach(range(1, 5) as $i)
            <x-ui::skeleton card />
        @endforeach
    </div>
@endif
```

---

## Migration Checklist

### Step 1: Prepare
- [ ] Read design system documentation (`/platform/docs/30-ui-design-system.md`)
- [ ] Read accessibility guide (`/platform/docs/50-accessibility-guide.md`)
- [ ] Install required tools (axe-core, csso-cli)

### Step 2: Component Migration
- [ ] Identify all inline `<style>` blocks
- [ ] Extract to separate CSS files
- [ ] Replace with design tokens
- [ ] Register in Assets config

### Step 3: Form Refactoring
- [ ] Replace custom form markup with `x-ui.form::` components
- [ ] Add proper labels
- [ ] Add error components
- [ ] Add help text components

### Step 4: Testing
- [ ] Test each migrated component
- [ ] Run accessibility audit
- [ ] Test keyboard navigation
- [ ] Test screen reader support

### Step 5: Cleanup
- [ ] Remove old CSS files
- [ ] Remove unused components
- [ ] Update documentation
- [ ] Update examples

---

## Quick Reference

### Design Tokens

```css
/* Colors */
--prof-primary: #206bc4
--prof-success: #2fb344
--prof-danger: #d63939
--prof-warning: #f5c06a
--prof-text-primary: #212529
--prof-text-secondary: #6c757d

/* Spacing */
--space-1: 4px
--space-2: 8px
--space-3: 12px
--space-4: 16px
--space-6: 24px

/* Border Radius */
--radius-sm: 2px
--radius-md: 4px
--radius-lg: 8px
--radius-full: 9999px
```

### Component Mapping

| Old Pattern | New Component |
|-------------|---------------|
| `<style>` block | External CSS + Design tokens |
| Custom modal | `<x-ui::modal>` |
| Custom card | `<x-ui::card>` |
| Custom alert | `<x-ui::alert>` |
| Custom form inputs | `<x-ui.form::input>`, `<x-ui.form::select>`, etc. |
| No data state | `<x-ui::empty-state>` |
| Loading spinner | `<x-ui::skeleton>` |
| Custom breadcrumb | `<x-ui.navigation::breadcrumb>` |
| Custom pagination | `<x-ui.navigation::pagination>` |

---

## Common Issues

### Issue: Design Tokens Not Working

**Solution:** Ensure design-tokens.css is loaded before other CSS files.

```php
// config/assets.php
'css' => [
    'design-tokens' => 'core/ui/css/design-tokens.css',
    'base-styles' => 'core/ui/css/base-styles.css',
    // ... other CSS
],
```

### Issue: Component Not Found

**Solution:** Check component path and ensure directory exists.

```blade
<!-- Correct -->
<x-ui::modal />

<!-- Incorrect -->
<x-ui::modal /> <!-- File doesn't exist -->
```

### Issue: Assets Not Loading

**Solution:** Clear cache and recompile.

```bash
php artisan cache:clear
php artisan view:clear
php artisan config:clear
```

---

## Timeline

| Phase | Duration | Status |
|-------|----------|--------|
| Phase 1: Foundation | 1 week | ✅ Complete |
| Phase 2: Components | 2 weeks | ✅ Complete |
| Phase 3: Layouts | 1 week | ✅ Complete |
| Phase 4: Performance | 1 week | ✅ Complete |
| Phase 5: Migration | 2 weeks | 🔄 In Progress |

---

## Support

For questions or issues, refer to:
- Design System: `/platform/docs/30-ui-design-system.md`
- Accessibility: `/platform/docs/50-accessibility-guide.md`
- Assets: `/platform/docs/40-assets-optimization.md`
