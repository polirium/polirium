# UI Specification: Professional Table Action Buttons & Translations (Comprehensive)

## 1. Executive Summary

Standardize the "Action" column buttons across **all Admin Tables** to ensure visual consistency using **PowerGrid's native `actions()` method** with solid/outline button styling. This covers Core, Accounting, Customer, Product, and Vendor modules.

## 2. Visual & Behavior Design

### 2.1. Action Column Header

- **Label**: "Thao tác" (instead of "Action")
- **Translation Key**: `core/base::general.action`
- **Column Definition**: `Column::action(trans('core/base::general.action'))`
- **Alignment**: Center (handled by PowerGrid)

### 2.2. Button Styles

All action buttons MUST use **PowerGrid's native `actions()` method** with `Button::add()`.

> ⚠️ **DO NOT** use view-based columns for action buttons (i.e., `->add('action', fn() => view(...)->render())`).

#### Edit Button

- **Icon**: `pencil` with `['class' => 'icon']`
- **Class**: `btn btn-primary btn-icon btn-sm` (solid blue background, white icon)
- **Tooltip**: `__('Sửa')` or `trans('core/base::general.edit')`

#### Delete Button

- **Icon**: `trash` with `['class' => 'icon']`
- **Class**: `btn btn-outline-danger btn-icon btn-sm` (red border, red icon, transparent background)
- **Tooltip**: `__('Xóa')` or `trans('core/base::general.delete')`
- **Confirm**: `'wire:confirm' => __('Bạn có chắc chắn muốn xóa?')`

### 2.3. Button Spacing

- Add `me-1` class to all buttons except the last one in the group.

## 3. Implementation Pattern (Reference: CustomerTable)

```php
use Polirium\Datatable\Button;
use Polirium\Datatable\Column;

// In columns() method:
Column::action(trans('core/base::general.action')),

// actions() method:
public function actions(Model $row): array
{
    return [
        Button::add('edit')
            ->slot(tabler_icon('pencil', ['class' => 'icon']))
            ->id()
            ->class('btn btn-primary btn-icon btn-sm me-1')
            ->attributes([
                'aria-label' => __('Sửa'),
                'title' => __('Sửa'),
            ])
            ->dispatch('show-modal-edit', ['id' => $row->id]),

        Button::add('delete')
            ->slot(tabler_icon('trash', ['class' => 'icon']))
            ->id()
            ->class('btn btn-outline-danger btn-icon btn-sm')
            ->attributes([
                'aria-label' => __('Xóa'),
                'title' => __('Xóa'),
                'wire:confirm' => __('Bạn có chắc chắn muốn xóa?'),
            ])
            ->dispatch('trigger-delete', ['id' => $row->id]),
    ];
}
```

### Authorization Pattern

Wrap buttons in permission checks when needed:

```php
public function actions(Model $row): array
{
    $actions = [];

    if (auth()->user()->can('module.edit')) {
        $actions[] = Button::add('edit')
            ->slot(tabler_icon('pencil', ['class' => 'icon']))
            ->id()
            ->class('btn btn-primary btn-icon btn-sm me-1')
            ->attributes([
                'aria-label' => __('Sửa'),
                'title' => __('Sửa'),
            ])
            ->dispatch('show-modal-edit', ['id' => $row->id]);
    }

    if (auth()->user()->can('module.destroy')) {
        $actions[] = Button::add('delete')
            ->slot(tabler_icon('trash', ['class' => 'icon']))
            ->id()
            ->class('btn btn-outline-danger btn-icon btn-sm')
            ->attributes([
                'aria-label' => __('Xóa'),
                'title' => __('Xóa'),
                'wire:confirm' => __('Bạn có chắc chắn muốn xóa?'),
            ])
            ->dispatch('trigger-delete', ['id' => $row->id]);
    }

    return $actions;
}
```

## 4. Translation Updates

### `platform/core/base/resources/lang/vi/general.php`

- `'action' => 'Thao tác'`
- `'actions' => 'Thao tác'`
- `'edit' => 'Sửa'`
- `'delete' => 'Xóa'`

## 5. Implementation Targets (19+ Files)

### Core Module

1.  `platform/core/base/src/Http/Livewire/Brand/Datatable/BrandTable.php`
2.  `platform/core/base/src/Http/Livewire/Branch/Datatable/BranchTable.php`
3.  `platform/core/base/src/Http/Livewire/Roles/Datatable/RoleTable.php`
4.  `platform/core/base/src/Http/Livewire/Table/ActivityLogTable.php`
5.  `platform/core/base/src/Http/Livewire/Users/Datatable/UserTable.php`

### Accounting Module

6.  `platform/modules/accounting/src/Http/Livewire/Index/Datatable/AccountingTable.php`
7.  `platform/modules/accounting/src/Http/Livewire/Invoice/Datatable/InvoiceTable.php`
8.  `platform/modules/accounting/src/Http/Livewire/Payment/Datatable/PaymentTable.php`
9.  `platform/modules/accounting/src/Http/Livewire/Payment/Refund/Datatable/PaymentRefundTable.php`

### Customer Module

10. `platform/modules/customer/src/Http/Livewire/Index/Datatable/CustomerTable.php` ✅ **Done (Reference)**
11. `platform/modules/customer/src/Http/Livewire/CustomerGroup/Datatable/CustomerGroupTable.php`

### Vendor Module

12. `platform/modules/vendor/src/Http/Livewire/Index/Datatable/VendorTable.php`
13. `platform/modules/vendor/src/Http/Livewire/VendorGroup/Datatable/VendorGroupTable.php`
14. `platform/modules/vendor/src/Http/Livewire/Purchase/Datatable/PurchaseTable.php`
15. `platform/modules/vendor/src/Http/Livewire/Transfer/Datatable/TransferTable.php`
16. `platform/modules/vendor/src/Http/Livewire/Refund/Datatable/RefundTable.php`

### Product Module

17. `platform/modules/product/src/Http/Livewire/Payment/PaymentMethodTable.php` ✅ **Done**
18. `platform/modules/product/src/Http/Livewire/Payment/SaleChannelTable.php` ✅ **Done**
19. `platform/modules/product/src/Http/Livewire/Payment/DeliveryPartnerTable.php` ✅ **Done**
20. `platform/modules/product/src/Http/Livewire/Index/Datatable/ProductListTable.php`
21. `platform/modules/product/src/Http/Livewire/PriceSetting/Datatable/PriceSettingTable.php`
22. `platform/modules/product/src/Http/Livewire/Stock/Datatable/StockTable.php`

## 6. Verification

- Visit each page to confirm:
    - ✅ Buttons use PowerGrid native `actions()` (rendered via `pgRenderActions`)
    - ✅ Edit button is solid blue (`btn-primary`) with white pencil icon
    - ✅ Delete button is red outline (`btn-outline-danger`) with red trash icon
    - ✅ Action column header shows "THAO TÁC"
    - ✅ Buttons have proper `aria-label` and `title` attributes

## 7. Change History

| Date       | Change                                                                                                                                                                                                                               |
| ---------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| 2026-02-11 | **MAJOR UPDATE**: Changed from `btn-ghost-*` to `btn-primary`/`btn-outline-danger`. Switched from view-based columns to PowerGrid native `actions()` method. Updated 3 payment tables (PaymentMethod, SaleChannel, DeliveryPartner). |
| Initial    | Original spec with `btn-ghost-primary` and `btn-ghost-danger` styling.                                                                                                                                                               |
