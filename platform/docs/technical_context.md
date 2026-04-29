# Polirium Technical Context

## Overview

Polirium is a modular ERP platform built on Laravel (v12.x). It follows a strict modular architecture where the core framework provides base services, and business logic is encapsulated in modules.

## Architecture Structure

### 1. Platform Core (`platform/core`)

Located in `platform/core`, this contains the foundation of the system.

- **Base (`core/base`)**: Foundational traits, services, and models (User, Role, etc.).
- **Media (`core/media`)**: File management system using Spatie Media Library.
- **Settings (`core/settings`)**: System configuration management.
- **UI (`core/ui`)**: Base UI components (Tabler), assets, and layout management.
- **Support (`core/support`)**: Helper functions and common traits.

### 2. Modules (`platform/modules`)

Business logic resides here. Each module (e.g., `product`, `sale`, `accounting`) is a self-contained Laravel package with its own:

- Routes (`routes/web.php`)
- Controllers & Livewire Components
- Views & Langs
- Migrations
- Configs (specifically `permissions.php`)

### 3. Packages (`platform/packages`)

Third-party or custom packages optimized for Polirium.

- **Laravel Tabler Icons**: UI icons.
- **Laravel Impersonate**: User impersonation logic (customized).

## Key Concepts & Helpers

### Module Autoloading

Modules are loaded via `Polirium\Core\Base\Services\LoadAndPublishDataTrait`. This trait automatically discovers:

- `config/*.php` (merged)
- `routes/web.php`, `routes/api.php`
- `resources/views` using `loadViews()`
- `resources/lang` using `loadTranslations()`
- `database/migrations` using `loadMigrations()`

### Permission System

Permissions are declarative, defined in `config/permissions.php` within each module.

- Structure: `name` (translation key), `flag` (unique string), `parent_flag`.
- Usage: `$user->hasPermission('flag')`.
- Middleware: `core` (web), `auth` (authentication), `can:permission.flag` (authorization).

### Livewire Integration

- Components are aliased in `config/livewire.php` of each module.
- Naming convention: `Module::Component.Path` mapping to fully qualified class names.
- Example: `modules/product::purchase.order.view` -> `Polirium\Modules\Product\Http\Livewire\Purchase\OrderComponent`.

## Technology Stack

- **Backend**: Laravel 12, PHP 8.3
- **Frontend**: Livewire 3, Alpine.js, Blade
- **CSS Framework**: Tabler (Bootstrap-based)
- **Database**: MySQL

## Design Guidelines

- **Modularity**: Never modify `platform/core` for business logic; use `platform/modules`.
- **UI**: Use standard path `core/ui` components for consistency.
- **Performance**: Use `repo->cache()` queries where possible.
