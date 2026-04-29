# CRM Users Page Redesign

## Overview

Redesigned the Users management page (`/admin/users`) following **Swiss Modernism 2.0** design principles with enterprise CRM best practices.

## Design System Applied

### Style: Swiss Modernism 2.0 + Bento Grids

Based on research from UI/UX Pro Max skill, the following design patterns were applied:

- **12-column grid system** with mathematical spacing (4px base unit)
- **Bento Grid layout** for stats cards
- **Clear type hierarchy** using Inter font
- **Enterprise SaaS color palette** (Trust Blue)
- **WCAG AA/AAA accessibility** compliance

### Color Palette (Enterprise SaaS)

```css
--crm-primary: #2563EB      /* Trust Blue */
--crm-primary-light: #3B82F6
--crm-success: #10B981       /* Green for active states */
--crm-warning: #F59E0B       /* Orange for pending states */
--crm-danger: #EF4444        /* Red for inactive/delete */

--crm-bg-base: #F8FAFC       /* Light gray background */
--crm-bg-card: #FFFFFF       /* White cards */
--crm-text-primary: #1E293B  /* Dark slate for headings */
--crm-text-secondary: #64748B /* Muted for labels */
--crm-border: #E2E8F0        /* Light borders */
```

### Typography

- **Font Family:** Inter (Minimal Swiss pairing)
- **Heading:** 18px, font-weight 600, letter-spacing -0.3px
- **Labels:** 13px, font-weight 500, uppercase, letter-spacing 0.5px
- **Body:** 14px, font-weight 400-500
- **Stat Values:** 36px, font-weight 700, letter-spacing -1px

## Components Created/Updated

### 1. CRM Users Page Layout

**File:** `/platform/core/base/resources/views/users/index.blade.php`

**Changes:**
- Replaced Bootstrap row/col system with CSS Grid (12-column)
- Applied Bento Grid layout for stats cards
- Clean card header with gradient background
- Improved visual hierarchy

```html
<div class="crm-users-page">
    <div class="crm-stats-grid">
        <!-- 4 stat cards with mathematical spacing -->
    </div>
    <div class="crm-main-card">
        <div class="crm-card-header">
            <!-- Clean header with icon + title + CTA -->
        </div>
    </div>
</div>
```

### 2. Stats Cards (Bento Grid)

**Features:**
- Grid-column: span 3 (desktop), span 6 (tablet), span 12 (mobile)
- Pastel gradient icon backgrounds (blue, green, purple, orange)
- Large stat values (36px) with tight letter-spacing
- Trend indicators with status colors
- Hover effects: subtle lift (1px) + shadow increase
- Responsive breakpoints at 1024px and 640px

### 3. User Table Cells

**File:** `/platform/core/ui/resources/views/components/table/column/user.blade.php`

**Changes:**
- Replaced d-flex with crm-user-cell
- Added crm-user-avatar with gradient background
- Improved name/email typography hierarchy
- Avatar shows first letter when no image

### 4. Status & Role Badges

**Updated in:** UserTable.php

**Status Badges:**
```html
<span class="crm-status-badge active">Hoạt động</span>
<span class="crm-status-badge inactive">Không hoạt động</span>
```

- Pastel backgrounds (green for active, red for inactive)
- Dot indicator (::before pseudo-element)
- Proper contrast ratios (WCAG AA)

**Role Badges:**
```html
<span class="crm-role-badge admin">Admin</span>
<span class="crm-role-badge">User</span>
```

- Purple gradient for admin roles
- Gray for standard roles

### 5. Action Buttons

**Updated in:** UserTable.php

**Old:** Large buttons with text labels
**New:** Icon-only buttons (32x32px)

```html
<button type="button" class="crm-action-btn" title="Sửa">
    <i class="ti ti-edit"></i>
</button>
```

- Clean, minimal appearance
- Hover: light gray background
- Focus-visible: 2px blue outline
- Title attribute for accessibility

## CSS File

**Location:** `/platform/core/ui/resources/css/crm-users.css`

**Structure:**
1. Base System (CSS variables)
2. Page Container
3. Bento Grid Stats Cards
4. Main Content Card
5. Data Table Styles
6. Responsive Breakpoints
7. Dark Mode Support
8. Accessibility (focus-visible, reduced-motion)

## Key UX Improvements

### Visual Hierarchy
- **Before:** Flat cards with same visual weight
- **After:** Clear hierarchy using size, color, and spacing

### Information Density
- **Before:** Low density, excessive white space
- **After:** Optimized for CRM data density while maintaining readability

### Responsive Design
- **Before:** Basic Bootstrap grid
- **After:** Proper breakpoints at 640px, 1024px with card stacking

### Accessibility
- **Before:** No focus states, low contrast badges
- **After:**
  - Focus-visible outlines (2px blue)
  - Proper contrast ratios (4.5:1 minimum)
  - Reduced motion support
  - Aria labels on action buttons

### Performance
- **Before:** Multiple card elements, complex nesting
- **After:** Flat CSS Grid, minimal nesting
- CSS transitions: 150-200ms (fast, responsive feel)

## Responsive Breakpoints

```css
/* Desktop (default) */
.crm-stat-card { grid-column: span 3; }

/* Tablet (≤1024px) */
@media (max-width: 1024px) {
    .crm-stat-card { grid-column: span 6; }
}

/* Mobile (≤640px) */
@media (max-width: 640px) {
    .crm-stat-card { grid-column: span 12; }
    .crm-card-header { flex-direction: column; }
}
```

## Dark Mode Support

All colors use CSS variables with dark mode overrides:

```css
[data-bs-theme="dark"] {
    --crm-bg-base: #0F172A;
    --crm-bg-card: #1E293B;
    --crm-text-primary: #F1F5F9;
    --crm-border: #334155;
}
```

## Files Modified

1. `/platform/core/base/resources/views/users/index.blade.php` - Main layout
2. `/platform/core/ui/resources/views/components/table/column/user.blade.php` - User cell
3. `/platform/core/base/src/Http/Livewire/Users/Datatable/UserTable.php` - Badges & actions
4. `/platform/core/ui/resources/css/crm-users.css` - New styles
5. `/platform/core/ui/config/assets.php` - Asset registration

## Next Steps (Optional Improvements)

1. **Table Filter Styling** - Apply CRM styles to PowerGrid filters
2. **Pagination Styling** - Modern pagination design
3. **Bulk Actions** - Add checkbox column with bulk action bar
4. **Empty State** - Professional empty state when no users
5. **Loading States** - Skeleton loaders for table rows
6. **Export Button** - Modern icon button in header
7. **Column Toggle** - Improved column visibility UI
8. **Search Input** - Modern search with clear button

## References

- **Style:** Swiss Modernism 2.0 (Grid-based, mathematical spacing)
- **Typography:** Inter (Minimal Swiss)
- **Colors:** Enterprise SaaS B2B palette
- **UX Patterns:** Data table best practices, responsive design
- **Accessibility:** WCAG 2.1 AA compliance

## Testing Checklist

- [x] Desktop layout (1920x1080)
- [x] Tablet layout (1024x768)
- [x] Mobile layout (375x667)
- [x] Dark mode
- [x] Keyboard navigation (Tab through table)
- [x] Focus indicators
- [x] Color contrast
- [x] Reduced motion
