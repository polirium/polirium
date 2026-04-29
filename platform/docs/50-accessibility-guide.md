# Accessibility Guide (WCAG 2.1 AA)

## Overview

This guide ensures Polirium ERP meets WCAG 2.1 AA compliance for accessibility.

---

## Color Contrast

### Requirements
- **Normal text:** Minimum 4.5:1 contrast ratio
- **Large text (18px+):** Minimum 3:1 contrast ratio
- **UI components:** Minimum 3:1 contrast ratio

### Testing Tools

```bash
# Install axe-core
npm install -g @axe-core/cli

# Scan website
axe http://polirium.test --tags wcag2aa
```

### Color Combinations

| Foreground | Background | Ratio | Pass/Fail |
|------------|------------|-------|-----------|
| #212529 (gray-900) | #ffffff | 16.4:1 | ✅ Pass |
| #6c757d (gray-600) | #ffffff | 4.6:1 | ✅ Pass |
| #adb5bd (gray-500) | #ffffff | 2.5:1 | ❌ Fail |
| #206bc4 (primary) | #ffffff | 5.8:1 | ✅ Pass |
| #2fb344 (success) | #ffffff | 4.6:1 | ✅ Pass |

### Do's and Don'ts

```css
/* ✅ Good - Sufficient contrast */
.text-primary {
    color: var(--prof-gray-900);
}

.badge-success {
    background: var(--prof-success);
    color: white;
}

/* ❌ Bad - Insufficient contrast */
.text-muted {
    color: var(--prof-gray-400); /* 2.5:1 - Too light */
}
```

---

## Focus Indicators

### Requirements
- All interactive elements must have visible focus state
- Focus indicator: 2px solid or equivalent
- Focus indicator must have 3:1 contrast against background

### Implementation

```css
/* Base styles (already in base-styles.css) */
:focus-visible {
    outline: 2px solid var(--prof-primary);
    outline-offset: 2px;
}

/* Hide outline when using mouse */
:focus:not(:focus-visible) {
    outline: none;
}
```

### Focus Order

Ensure logical tab order:

```html
<!-- ✅ Good - Logical order -->
<button>Button 1</button>
<button>Button 2</button>
<a href="/link">Link 1</a>

<!-- ❌ Bad - Confusing order -->
<div tabindex="2">Div 2</div>
<div tabindex="1">Div 1</div>
```

---

## Screen Reader Support

### Semantic HTML

```html
<!-- ✅ Good - Semantic elements -->
<nav aria-label="Main navigation">
    <ul>
        <li><a href="/dashboard">Dashboard</a></li>
    </ul>
</nav>

<main>
    <h1>Page Title</h1>
    <article>
        <h2>Article Title</h2>
        <p>Content...</p>
    </article>
</main>

<aside aria-label="Sidebar">
    <!-- Sidebar content -->
</aside>

<footer>
    <p>&copy; 2024 Polirium ERP</p>
</footer>
```

### ARIA Labels

```html
<!-- Icon-only buttons -->
<button aria-label="Close modal">
    <svg>...</svg>
</button>

<!-- Decorative icons -->
<img src="logo.png" alt="" role="presentation" />

<!-- Form labels -->
<label for="email">Email Address</label>
<input type="email" id="email" aria-describedby="email-help">
<p id="email-help" class="form-text">
    We'll never share your email with anyone.
</p>

<!-- Error messages -->
<div class="alert alert-danger" role="alert">
    <strong>Error:</strong> {{ $message }}
</div>
```

### Skip Links

```html
<!-- Already in base-styles.css -->
<a href="#main-content" class="skip-link">
    Skip to main content
</a>

<main id="main-content">
    <!-- Main content -->
</main>
```

---

## Keyboard Navigation

### Requirements
- All functionality must be accessible via keyboard
- No keyboard traps
- Clear focus indicators
- Skip repeated content

### Testing Checklist

- [ ] Tab through page - logical order
- [ ] Enter/Space activates buttons
- [ ] Escape closes modals/dropdowns
- [ ] Arrow keys navigate lists/menus
- [ ] Tab in forms moves between fields

### Keyboard Shortcuts

| Key | Action | Implementation |
|-----|--------|----------------|
| Tab | Next focusable element | Default browser |
| Shift + Tab | Previous focusable element | Default browser |
| Enter | Activate button/link | Default browser |
| Escape | Close modal/dropdown | Add event listener |
| Arrow Keys | Navigate lists | Use `role="listbox"` |

```javascript
// Example: Escape to close modal
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        const modal = document.querySelector('.modal.show');
        if (modal) {
            // Close modal
            const modalInstance = bootstrap.Modal.getInstance(modal);
            modalInstance?.hide();
        }
    }
});
```

---

## Forms Accessibility

### Required Fields

```blade
<!-- ✅ Good - Clear required indicator -->
<x-ui.form::label for="email" required>
    Email Address
</x-ui.form::label>
<input type="email" id="email" required aria-required="true">

<!-- ❌ Bad - No indication -->
<input type="email" placeholder="Email" required>
```

### Inline Validation

```php
// Livewire Component
public function updated($propertyName)
{
    $this->validateOnly($propertyName, [
        'email' => 'required|email',
        'password' => 'required|min:8',
    ]);
}

// In blade
<div class="form-group">
    <label for="email">Email</label>
    <input
        type="email"
        wire:model.live="email"
        id="email"
        aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
        aria-describedby="{{ $errors->has('email') ? 'email-error' : 'email-help' }}"
    >
    @if($errors->has('email'))
        <p id="email-error" class="invalid-feedback" role="alert">
            {{ $errors->first('email') }}
        </p>
    @else
        <p id="email-help" class="form-text">
            Enter your email address
        </p>
    @endif
</div>
```

### Autocomplete

```blade
<input type="email"
       wire:model="email"
       autocomplete="email"
       aria-label="Email address">

<input type="tel"
       wire:model="phone"
       autocomplete="tel"
       aria-label="Phone number">

<input type="password"
       wire:model="password"
       autocomplete="current-password"
       aria-label="Password">
```

---

## Alt Text for Images

### Guidelines

```blade
<!-- ✅ Good - Descriptive alt text -->
<img src="user-avatar.jpg" alt="Profile picture of John Doe">

<!-- ✅ Good - Decorative image -->
<img src="decoration.png" alt="" role="presentation">

<!-- ✅ Good - Informative image -->
<img src="sales-chart.png" alt="Bar chart showing 50% increase in Q3 sales">

<!-- ❌ Bad - Meaningless alt text -->
<img src="chart.png" alt="image">
<img src="photo.jpg" alt="12345.jpg">
```

### Testing

```bash
# Check for missing alt text
find . -name "*.blade.php" -exec grep -l '<img' {} \ | \
    xargs grep -n 'alt=""' | \
    grep -v 'role="presentation"'
```

---

## Data Tables

### Accessible Tables

```blade
<table class="table" role="table">
    <caption>List of users in the system</caption>
    <thead>
        <tr>
            <th scope="col">Name</th>
            <th scope="col">Email</th>
            <th scope="col">Role</th>
            <th scope="col">Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach($users as $user)
        <tr>
            <td scope="row">{{ $user->name }}</td>
            <td>{{ $user->email }}</td>
            <td>{{ $user->role }}</td>
            <td>
                <button aria-label="Edit {{ $user->name }}">Edit</button>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
```

---

## Modals

### Accessible Modal Pattern

```blade
<div class="modal fade"
     id="user-modal"
     tabindex="-1"
     aria-labelledby="user-modal-title"
     aria-hidden="true"
     role="dialog"
     aria-modal="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="user-modal-title" class="modal-title">Edit User</h5>
                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close">
                </button>
            </div>
            <div class="modal-body">
                <!-- Form content -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Cancel
                </button>
                <button type="submit" class="btn btn-primary">
                    Save
                </button>
            </div>
        </div>
    </div>
</div>
```

---

## Testing Checklist

### Automated Testing

```bash
# Install axe-core
npm install -g @axe-core/cli

# Run accessibility audit
axe http://polirium.test --tags wcag2aa --disable color-contrast

# Continuous integration
axe http://localhost:8000 --tags wcag2aa > accessibility-report.json
```

### Manual Testing

1. **Keyboard Navigation**
   - [ ] Tab through entire page
   - [ ] All interactive elements reachable
   - [ ] No keyboard traps
   - [ ] Visible focus indicators

2. **Screen Reader Testing**
   - [ ] NVDA (Windows)
   - [ ] VoiceOver (macOS)
   - [ ] TalkBack (Android)

3. **Color Contrast**
   - [ ] All text meets 4.5:1 ratio
   - [ ] Large text meets 3:1 ratio
   - [ ] UI components meet 3:1 ratio

4. **Forms**
   - [ ] All inputs have labels
   - [ ] Required fields marked
   - [ ] Error messages accessible
   - [ ] Validation feedback clear

---

## Common Issues & Fixes

### Issue: Low Contrast Text

```css
/* ❌ Bad */
.text-muted {
    color: #adb5bd; /* 2.5:1 - Too light */
}

/* ✅ Good */
.text-muted {
    color: #6c757d; /* 4.6:1 - Passes */
}
```

### Issue: Missing Alt Text

```blade
<!-- ❌ Bad -->
<img src="{{ $user->avatar }}">

<!-- ✅ Good -->
<img src="{{ $user->avatar }}" alt="{{ $user->name }}'s profile picture">
```

### Issue: No Focus Indicator

```css
/* ❌ Bad */
.button:focus {
    outline: none; /* Removes focus indicator */
}

/* ✅ Good */
.button:focus-visible {
    outline: 2px solid var(--prof-primary);
    outline-offset: 2px;
}
```

### Issue: Unlabeled Inputs

```blade
<!-- ❌ Bad -->
<input type="text" placeholder="Email">

<!-- ✅ Good -->
<label for="email">Email Address</label>
<input type="email" id="email" required>
```

---

## Resources

### Documentation
- [WCAG 2.1 Quick Reference](https://www.w3.org/WAI/WCAG21/quickref/)
- [ARIA Authoring Practices](https://www.w3.org/WAI/ARIA/apg/)
- [Accessible Rich Internet Applications](https://www.w3.org/TR/wai-aria-1.2/)

### Tools
- [axe DevTools](https://www.deque.com/axe/devtools/)
- [WAVE Browser Extension](https://wave.webaim.org/)
- [Lighthouse Accessibility](https://developers.google.com/web/tools/lighthouse/)
- [Color Contrast Analyzer](https://webaim.org/resources/contrastchecker/)

### Testing
- [NVDA Screen Reader](https://www.nvaccess.org/)
- [JAWS Screen Reader](https://www.freedomscientific.com/products/software/jaws/)
- [VoiceOver (macOS)](https://www.apple.com/accessibility/voiceover/)
