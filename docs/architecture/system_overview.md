# System Architecture Overview

## Structure

The project follows a modular Laravel architecture.

### Modules (`platform/modules`)

- **Product:** Handles product management, inventory, and point-of-sale (POS) payment interfaces (`payment`, `payment-v2`).
- **Vendor:** Vendor management.
- **Accounting:** Financial records.

### Core (`platform/core`)

- **Base:** Foundation classes, general resources, and common UI components (`core/base`).
- **UI:** Global styles and assets. Migrated to Google Fonts (**Inter**) for stability.

### Packages (`platform/packages`)

- **Core Datatable:** A customized PowerGrid wrapper for consistent table and card views. Supports standard mobile view toggles and unified state management via Alpine.js.

## Key Components

### Payment System

- **Views:**
    - `payment.blade.php` (Legacy/Standard View)
    - `tab-v2.blade.php`, `payment-view.blade.php` (V2 POS Interface)
- **Livewire Components:**
    - `ModalPaymentTypeComponent`: Handles the split payment logic.
- **Frontend Logic:**
    - Relies heavily on Alpine.js for interactivity (modals, calculations).
    - Uses `PoliriumKeepAlive` (JS) for session maintenance (Heartbeat, CSRF refresh).
