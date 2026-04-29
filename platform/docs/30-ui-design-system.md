# Polirium UI - Design System Documentation

## Design Tokens

### Colors

#### Primary Colors
| Token | Value | Usage |
|-------|-------|-------|
| `--prof-primary` | #206bc4 | Primary actions, links |
| `--prof-primary-light` | #e7f1ff | Primary backgrounds, badges |
| `--prof-primary-hover` | #185ab0 | Primary hover state |

#### Status Colors
| Token | Value | Usage |
|-------|-------|-------|
| `--prof-success` | #2fb344 | Success states, confirmations |
| `--prof-danger` | #d63939 | Error states, destructive actions |
| `--prof-warning` | #f5c06a | Warning states |
| `--prof-info` | #0dcaf0 | Informational states |

#### Neutral Colors
| Token | Value | Usage |
|-------|-------|-------|
| `--prof-white` | #ffffff | Backgrounds |
| `--prof-gray-50` | #f8f9fa | Secondary backgrounds |
| `--prof-gray-100` | #f1f3f5 | Tertiary backgrounds |
| `--prof-gray-900` | #212529 | Primary text |
| `--prof-gray-600` | #6c757d | Secondary text |

### Spacing Scale (4px base unit)

| Token | Value | Usage |
|-------|-------|-------|
| `--space-1` | 4px | XS spacing |
| `--space-2` | 8px | SM spacing |
| `--space-3` | 12px | MD spacing |
| `--space-4` | 16px | Default spacing |
| `--space-6` | 24px | LG spacing |
| `--space-8` | 32px | XL spacing |

### Border Radius

| Token | Value | Usage |
|-------|-------|-------|
| `--radius-sm` | 2px | Small elements |
| `--radius-md` | 4px | Default radius |
| `--radius-lg` | 8px | Cards, buttons |
| `--radius-xl` | 12px | Modals |
| `--radius-full` | 9999px | Pills, badges |

---

## Form Components

### Input Component

#### Basic Usage
```blade
<x-ui.form::input
    name="email"
    label="Email Address"
    type="email"
    required
    hint="We'll never share your email"
/>
```

#### With Icon
```blade
<x-ui.form::input
    name="search"
    label="Search"
    icon="search"
    placeholder="Search..."
/>
```

#### Horizontal Layout
```blade
<x-ui.form::input
    name="username"
    label="Username"
    horizontal
    labelWidth="col-sm-4"
    inputWidth="col-sm-8"
/>
```

#### Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `name` | string | - | Field name (required) |
| `label` | string | null | Field label |
| `type` | string | 'text' | Input type (text, email, tel, number, url, password) |
| `required` | bool | false | Show required indicator |
| `disabled` | bool | false | Disable input |
| `readonly` | bool | false | Make input readonly |
| `icon` | string | null | Tabler icon name |
| `hint` | string | null | Help text below input |
| `horizontal` | bool | false | Use horizontal layout |
| `compact` | bool | false | Use smaller size |

#### Input Types (UX Best Practice)

| Type | Use For | Example |
|------|---------|---------|
| `email` | Email addresses | `type="email"` |
| `tel` | Phone numbers | `type="tel"` |
| `number` | Numeric values | `type="number"` |
| `url` | URLs | `type="url"` |
| `password` | Passwords | `type="password"` |
| `date` | Dates | `type="date"` |
| `datetime-local` | Date & time | `type="datetime-local"` |

### Label Component

```blade
<x-ui.form::label for="email" required>
    Email Address
</x-ui.form::label>
```

### Error Component

```blade
<x-ui.form::error :attribute="'email'" />
```

### Help Component

```blade
<x-ui.form::help text="Your email will be used for account recovery" />
```

---

## Interface Components

### Modal Component

```blade
<x-ui::modal id="modal-id" size="modal-lg">
    <x-slot:title>
        Modal Title
    </x-slot>

    <x-slot:body>
        Modal body content
    </x-slot>

    <x-slot:footer>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            Cancel
        </button>
        <button type="submit" class="btn btn-primary">
            Save
        </button>
    </x-slot>
</x-ui::modal>
```

#### Modal Sizes

| Size | Class | Width |
|------|-------|-------|
| Small | `modal-sm` | 400px |
| Default | - | 500px |
| Large | `modal-lg` | 800px |
| Extra Large | `modal-xl` | 1140px |

### Card Component

```blade
<x-ui::card>
    <x-slot:header>
        <h3 class="card-title">Card Title</h3>
    </x-slot>

    <x-slot:body>
        Card content
    </x-slot>

    <x-slot:footer>
        Card footer
    </x-slot>
</x-ui::card>
```

### Alert Component

```blade
<x-ui::alert variant="success" dismissible>
    <strong>Success!</strong> Your changes have been saved.
</x-ui::alert>
```

#### Alert Variants

| Variant | Class | Usage |
|---------|-------|-------|
| Success | `alert-success` | Success messages |
| Danger | `alert-danger` | Error messages |
| Warning | `alert-warning` | Warning messages |
| Info | `alert-info` | Informational messages |

---

## Responsive Breakpoints

| Breakpoint | Width | Devices |
|------------|-------|---------|
| `xs` | 0px | Mobile (portrait) |
| `sm` | 576px | Mobile (landscape) |
| `md` | 768px | Tablets |
| `lg` | 992px | Small laptops |
| `xl` | 1200px | Desktops |
| `xxl` | 1400px | Large desktops |

---

## Z-Index Scale

| Value | Usage |
|-------|-------|
| 1000 | Dropdowns |
| 1020 | Sticky headers |
| 1030 | Fixed elements |
| 1040 | Modal backdrop |
| 1050 | Modal |
| 1060 | Popovers |
| 1070 | Tooltips |
| 1080 | Toast notifications |

---

## Accessibility Guidelines

### Color Contrast
- Minimum 4.5:1 for normal text
- Minimum 3:1 for large text (18px+)
- Minimum 3:1 for UI components

### Focus States
- All interactive elements must have visible focus state
- Focus indicator: 2px solid primary color
- Focus offset: 2px

### Keyboard Navigation
- Tab: Navigate forward
- Shift + Tab: Navigate backward
- Enter/Space: Activate buttons
- Escape: Close modals, dropdowns

### Screen Readers
- Use semantic HTML (`<button>`, `<input>`, `<label>`)
- Add `aria-label` for icon-only buttons
- Add `aria-describedby` for help text
- Use `sr-only` class for screen-reader-only text
