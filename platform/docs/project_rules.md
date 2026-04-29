# Polirium Project Rules

## 1. Modular Architecture Rules

- **Encapsulation**: Code related to a specific business domain MUST reside in its corresponding module directory (`platform/modules/{module-name}`).
- **Namespace**:
    - Core: `Polirium\Core\{Component}\`
    - Modules: `Polirium\Modules\{Module}\`
    - Packages: `Polirium\{Package}\`
- **Depdendency**: Modules should rely on Core contracts/interfaces rather than direct implementation where possible.

## 2. Permission Consistency

- **Definition**: All permissions must be defined in `{module}/config/permissions.php`.
- **Translation**: Every permission MUST have a corresponding translation in `resources/lang/{lang}/permission.php`.
- **Granularity**: Use parent-child structure (`parent_flag`) for grouping (e.g., `products` -> `products.create`).

## 3. UI/UX Consistency

- **Framework**: Use Tabler UI classes. Do not introduce customized CSS unless absolutely necessary.
- **Icons**: Use `vigstudio/laravel-tabler-icons` (e.g., `<x-tabler-icon name="user" />` or `@svg('ti-user')`).
- **Translations**: UI labels must be translatable using `trans('module::file.key')`.

## 4. Coding Standards (Laravel 12+)

- **Types**: Use strict typing (`declare(strict_types=1);`) where applicable.
- **Traits**: Use Traits for shared model functionality (e.g., `HasUuid`, `Impersonate`).
- **Controllers**: Keep controllers thin; delegate logic to Services or Actions.
- **Service Providers**: Always call `loadTranslations`, `loadViews`, `loadMigrations` in `boot()` method of Module ServiceProviders.

## 5. Git & Version Control

- **Submodules**: Ensure `platform/core` and `platform/packages` are committed as submodules.
- **Commits**: Use conventional commits (feat, fix, refactor, chore, docs).
