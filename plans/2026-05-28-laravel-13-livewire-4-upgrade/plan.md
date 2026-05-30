---
title: "Laravel 13 and Livewire 4 Upgrade Plan"
description: "Upgrade Polirium from Laravel 12 to Laravel 13 while keeping Livewire on the latest 4.x line and validating the modular ERP surface."
status: pending
priority: P1
effort: 3-6d
issue:
branch: codex/laravel-13-livewire-4-upgrade
tags: [backend, infra, tech-debt, laravel, livewire]
created: 2026-05-28
---

# Laravel 13 and Livewire 4 Upgrade Plan

## Current State

- Root app: Laravel 12.58.0, PHP 8.3.17, Livewire 4.3.0.
- Root Composer constraints still allow PHP 8.2 and Laravel 12 only.
- `platform/core` blocks Laravel 13 because it requires `laravel/framework: ^10.0|^11.0|^12.0`.
- Local packages/modules are loaded via path repositories.
- `platform/core` is a submodule, so core changes need a separate commit and parent pointer update.
- Frontend build uses Laravel Mix 6, not Vite.
- Test suite is very thin: only scaffold `tests/Feature/ExampleTest.php` and `tests/Unit/ExampleTest.php` found.

## Target

- Laravel framework: latest 13.x available at implementation time.
- Livewire: latest 4.x available at implementation time.
- PHP constraint: move app-owned Composer manifests to `^8.3` unless a package is intentionally framework-agnostic.
- Keep current UI/UX and module behavior unchanged.
- No opportunistic rewrite to a Laravel 13 skeleton unless required by a breaking change.

## Official Upgrade Inputs

- Laravel 13 upgrade guide lists these high-impact dependency moves: `laravel/framework:^13.0`, `laravel/boost:^2.0`, `laravel/tinker:^3.0`, `phpunit/phpunit:^12.0`, plus review request forgery protection and other breaking changes.
- Livewire 4 guide says v4 is mostly compatible, but endpoint hashes, `request`/`commit` hook deprecations, `wire:model` timing behavior, config, routing, and method signatures must be reviewed.

## Package Support Audit

Audit command used:

```bash
composer prohibits laravel/framework 13.0 -t
```

Current blockers:

| Package | Current | Laravel 13 status | Action |
|---|---:|---|---|
| Root app `polirium/polirium` | dev-main | Blocks via `laravel/framework:^12.0` | Change root constraint to `^13.0` |
| `polirium/core` | dev-main | Blocks via `laravel/framework:^10.0|^11.0|^12.0` | Patch in `platform/core/composer.json` to include `^13.0` |
| `barryvdh/laravel-debugbar` | v3.16.5 | Current v3 blocks; v4.2.8 supports Laravel 13 | Upgrade dev dependency to `^4.2` |
| `kalnoy/nestedset` | v6.0.6 | Current v6 blocks; v7.0.0 supports Laravel 13 only | Upgrade `platform/core` dependency to `^7.0` and regression test category/tree data |
| `laravel/boost` | v1.8.13 | Current v1 blocks; v2.4.8 supports Laravel 13 | Upgrade dev dependency to `^2.0` or latest `^2.4` |
| `laravel/tinker` | v2.11.1 | Current v2 blocks; v3.0.2 supports Laravel 13 | Upgrade to `^3.0` |
| `laravel/roster` | v0.2.9 | Transitive blocker through Boost v1 | Resolved by Boost v2 |
| `vigstudio/laravel-tabler-icons` | 2.0.0 local/path | Blocks via `illuminate/support:^11.0|^12.0` and `illuminate/view:^11.0|^12.0` | Patch local `platform/packages/laravel-tabler-icons/composer.json` to include `^13.0`; run icon render smoke tests |

Installed packages already showing Laravel 13-compatible constraints:

- `cmgmyr/messenger`
- `diglactic/laravel-breadcrumbs`
- `laravel/fortify`
- `laravel/horizon`
- `laravel/passkeys`
- `laravel/sanctum`
- `laravel/sail`
- `laravel/sentinel`
- `laravolt/avatar`
- `livewire/livewire`
- `maatwebsite/excel`
- `mcamara/laravel-localization`
- `polirium/datatable`
- `polirium/laravel-impersonate`
- `spatie/laravel-activitylog`
- `spatie/laravel-medialibrary`
- `spatie/laravel-package-tools`
- `spatie/laravel-permission`

Packages with broad or old constraints that still need runtime smoke testing even if Composer allows them:

- `lavary/laravel-menu`: old package, broad `illuminate/support >=5.0` and `illuminate/view >=5.0`.
- `polirium/laravel-impersonate`: local fork supports Laravel 13 by constraint, but must test guard/session impersonation.

## Unsupported Package Policy

If Composer or runtime tests show an upstream package does not support Laravel 13:

1. Prefer upgrading to an upstream release that officially supports Laravel 13.
2. If no supported release exists, pull/fork the package into `/Users/vingamagic/Developer/php/polirium/platform/packages/{package-name}`.
3. Change root `composer.json` repositories to use the path package.
4. Keep the original namespace when possible to reduce app changes.
5. Patch only compatibility issues required for Laravel 13 and PHP 8.3.
6. Add a short `UPGRADE_NOTES.md` in the local package with:
   - upstream source URL and version/commit copied
   - changed Composer constraints
   - code changes made for Laravel 13
   - tests/smoke checks required
7. Add or update package-level tests if the package has a test harness. If not, add app-level smoke tests covering the package behavior.

Candidate package pull/fork commands:

```bash
mkdir -p platform/packages
git clone <upstream-url> platform/packages/<package-name>
```

After pulling a package into `platform/packages`, use a path repository and require the local package name/version alias if needed:

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "./platform/packages/*",
      "options": {
        "symlink": true
      }
    }
  ]
}
```

## Phase 1 - Preflight and Branch

1. Confirm production PHP and extensions support PHP 8.3+.
2. Create branch `codex/laravel-13-livewire-4-upgrade`.
3. Snapshot current dependency state:
   - `composer show --direct`
   - `composer outdated --direct`
   - `php artisan about`
   - `php artisan route:list`
4. Back up staging database and `.env` values.
5. Disable only non-essential queues/schedulers in staging during first upgrade test.

Success criteria:
- Branch exists.
- Dependency and route baselines are saved.
- Staging rollback path is confirmed.

## Phase 2 - Dependency Compatibility Audit

Review and update Composer constraints in these manifests:

- `/Users/vingamagic/Developer/php/polirium/composer.json`
- `/Users/vingamagic/Developer/php/polirium/platform/core/composer.json`
- `/Users/vingamagic/Developer/php/polirium/platform/packages/core-datatable/composer.json`
- `/Users/vingamagic/Developer/php/polirium/platform/packages/laravel-impersonate/composer.json`
- `/Users/vingamagic/Developer/php/polirium/platform/packages/laravel-tabler-icons/composer.json`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/*/composer.json`

Expected constraint changes:

- Root app:
  - `php: ^8.3`
  - `laravel/framework: ^13.0`
  - `laravel/tinker: ^3.0`
  - `laravel/boost: ^2.0`
  - `phpunit/phpunit: ^12.0`
- `platform/core`:
  - add Laravel 13 support to `laravel/framework`
  - keep `livewire/livewire:^4.0`
  - change `kalnoy/nestedset` from `^6.0` to `^7.0`
  - verify latest compatible Fortify and Horizon constraints
- `platform/packages/laravel-tabler-icons`:
  - add `^13.0` to `illuminate/support`
  - add `^13.0` to `illuminate/view`
- App-owned modules/packages:
  - move PHP constraints to `^8.3` where they are not published compatibility libraries
  - verify Laravel 13 support where `illuminate/*` or `laravel/framework` is required

Run dependency resolution:

```bash
composer update laravel/framework laravel/boost laravel/tinker phpunit/phpunit nunomaduro/collision laravel/pint laravel/sail laravel/sanctum laravel/fortify laravel/horizon livewire/livewire barryvdh/laravel-debugbar kalnoy/nestedset -W
```

Success criteria:
- `composer update -W` resolves without forcing unrelated downgrades.
- `composer validate` passes for root and edited path packages.
- `composer.lock` contains Laravel 13.x and Livewire 4.x.

## Phase 3 - Laravel 13 Code Compatibility

Audit the Laravel 13 upgrade guide against local code.

Known local hotspots:

- `/Users/vingamagic/Developer/php/polirium/app/Http/Middleware/VerifyCsrfToken.php`
- `/Users/vingamagic/Developer/php/polirium/app/Http/Kernel.php`
- `/Users/vingamagic/Developer/php/polirium/config/sanctum.php`
- `/Users/vingamagic/Developer/php/polirium/platform/core/settings/src/Drivers/Factory.php`
- `/Users/vingamagic/Developer/php/polirium/platform/packages/laravel-impersonate/src/ImpersonateServiceProvider.php`

Checklist:

1. Request forgery protection:
   - confirm `VerifyCsrfToken` middleware class path and aliases still match Laravel 13 expectations.
   - verify Sanctum CSRF flow and `/sanctum/csrf-cookie`.
2. Manager `extend` callback binding:
   - inspect custom factory/manager extensions for Laravel 13 callback binding changes.
3. Domain route registration precedence:
   - compare route order for root app and platform modules.
4. Cache/session naming:
   - ensure no production code relies on old generated cache prefixes or session cookie names.
5. Pagination Bootstrap view names:
   - search for direct pagination view names and update if needed.
6. Queue changes:
   - audit Horizon and queue event listeners for renamed `QueueBusy` properties or changed exception payloads.
7. Database behavior:
   - audit MySQL/MariaDB `upsert` usage and `DELETE ... JOIN ... ORDER BY ... LIMIT` queries if present.

Success criteria:
- `php artisan package:discover` passes.
- `php artisan config:clear && php artisan route:clear && php artisan view:clear` passes.
- `php artisan migrate --pretend` runs cleanly.
- Login, Fortify flows, Sanctum CSRF, route generation, queues, and Horizon boot locally.

## Phase 4 - Livewire 4 Audit

Livewire is already installed at 4.3.0, so this is a compatibility cleanup and latest 4.x verification phase.

Known local hotspots:

- `/Users/vingamagic/Developer/php/polirium/platform/core/ui/resources/views/base/base.blade.php`
- `/Users/vingamagic/Developer/php/polirium/platform/core/base/resources/views/roles/modal/modal-create-role.blade.php`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/accounting/resources/views/payment/datatable/detail.blade.php`
- `/Users/vingamagic/Developer/php/polirium/platform/modules/vendor/resources/views/purchase/order/view.blade.php`

Checklist:

1. Replace deprecated `Livewire.hook('request', ...)` with `Livewire.interceptRequest(...)`.
2. Review `Livewire.hook('message.processed', ...)`; migrate if broken or deprecated in current 4.x.
3. Keep `morph.updated` only if verified in current Livewire 4 lifecycle.
4. Review `wire:model.blur` in purchase order forms:
   - if v3 timing is required, switch to `wire:model.live.blur`.
   - if v4 timing is desired, leave as-is and test recalculation.
5. Confirm no reverse proxy, firewall, route middleware, or JS assumes `/livewire/*`; v4 uses `/livewire-{hash}/*`.
6. Clear compiled views and config after publishing or config updates.

Success criteria:
- Livewire requests return 200.
- File uploads, modals, datatables, inline forms, and purchase order totals work.
- Browser console has no Livewire deprecation errors on core pages.

## Phase 5 - Test Expansion Before Final QA

Current automated tests are insufficient for a framework upgrade. Add focused smoke coverage before claiming completion.

Minimum tests:

- Auth/login redirects to admin/home correctly.
- Unauthorized dashboard widgets are hidden.
- Product copy creates a distinct code with `(copy)` suffix behavior already expected by the app.
- Core module routes load for authenticated users.
- Livewire component smoke tests for datatable, modal, and purchase order edit flow.
- Permission-gated widget rendering.

Commands:

```bash
php artisan test
vendor/bin/pint --test
npm run production
```

Success criteria:
- Tests pass locally.
- Assets build.
- No Laravel deprecation or fatal errors in logs.

## Phase 6 - Staging QA

Manual checks on staging:

1. Login/logout/password reset.
2. Dashboard with multiple roles and branches.
3. Product list, product copy, product delete permission.
4. Accounting widgets and payment datatables.
5. Vendor purchase order edit with `wire:model.blur` fields.
6. Sale/customer/vendor/task module routes.
7. Media upload/download.
8. Queue jobs and Horizon dashboard.
9. Browser console and network tab for Livewire 4 endpoints.

Success criteria:
- No 500 errors in Laravel log.
- No Livewire failed requests.
- Permission differences remain correct.
- Key workflows match Laravel 12 baseline.

## Phase 7 - Release and Rollback

Release steps:

1. Deploy during low-traffic window.
2. Put app in maintenance mode if migrations or cache changes require it.
3. Pull code and submodule pointer.
4. Run `composer install --no-dev --prefer-dist --optimize-autoloader`.
5. Run `php artisan migrate --force`.
6. Run cache warmup:
   - `php artisan config:cache`
   - `php artisan route:cache`
   - `php artisan view:cache`
7. Restart queues/Horizon/PHP-FPM.

Rollback:

1. Revert to previous Git SHA and previous `platform/core` submodule SHA.
2. Restore previous `composer.lock`.
3. Run `composer install --no-dev --prefer-dist --optimize-autoloader`.
4. Restore DB backup only if migrations changed data or schema incompatibly.
5. Restart PHP-FPM and queues.

## Main Risks

- Path repository constraints block Composer resolution.
- Livewire request interception code currently uses a deprecated hook.
- Weak test coverage means manual QA is mandatory.
- Laravel 13 CSRF/request forgery changes may affect Sanctum/Fortify flows.
- `platform/core` submodule can be upgraded but parent repo must commit the new pointer.
- Laravel Mix 6 may still build, but old frontend tooling increases release risk.

## Recommended Scope

Do this as a framework compatibility release only:

- Upgrade Laravel and first-party packages.
- Keep Livewire on latest 4.x.
- Fix only required compatibility issues.
- Add smoke tests around auth, permissions, product copy, and Livewire screens.
- Defer Laravel Mix to Vite migration to a separate plan unless the build breaks.

## Open Questions

- What production PHP version and extensions are currently installed?
- Does production have reverse proxy, WAF, or CDN rules for `/livewire/*`?
- Are Horizon workers supervised by Supervisor, systemd, Docker, or another process manager?
- Which roles/users should be used as QA fixtures for permission checks?
