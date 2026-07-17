# Polirium Full Audit And Fix Plan

Date: 2026-06-27

## Baseline Context

- Runtime: PHP 8.3, Laravel 13.12.0, Livewire 4.3.0, PHPUnit 12.5.28, Pint 1.29.1.
- Database: MySQL.
- Frontend stack: Laravel Mix, Livewire, Alpine.js, Tabler UI. React-specific frontend guidelines are not the primary standard for this repository; use Polirium UI docs, Livewire/Alpine rules, and browser evidence for FE logic checks.
- Search index: `.agents/skills/polirium/data/*.csv`.

## What Counts As An Issue

An item is an issue only when there is evidence for at least one of these:

1. Runtime error, failing test, PHP/JS syntax error, build failure, or logged exception.
2. Backend behavior violates route, controller, service, model, validation, database, or business-rule contract.
3. Permission or branch isolation allows unauthorized access, hides authorized access, or exposes data across users/branches.
4. UI behavior blocks a normal workflow, submits wrong data, loses validation feedback, has broken Livewire/Alpine state, console error, failed network request, or unusable layout on expected viewport sizes.
5. Documentation, index data, menu, route, translation, or config is stale enough to mislead development or operation.

Stylistic preference is not an issue unless it breaks a project rule, accessibility, maintainability, or a real workflow.

## Evidence Format For Every Issue

Each issue must be recorded with:

- Problem: exact file/line, route, command output, log entry, database row, browser action, screenshot, or failing test.
- Why it is a problem: violated rule, permission expectation, user workflow, or failure condition.
- Root cause: traced through route -> middleware -> controller/request -> service/action -> model/query -> database/view.
- Fix plan: smallest change that addresses the root cause.
- Verification plan: exact command, query, browser action, screenshot, or test proving the fix.
- Result after fix: changed files and fresh verification output.

## Audit Order

1. Re-index project knowledge.
   - Compare modules, routes, permissions, menus, controllers, Livewire components, models, migrations, views, services, widgets, configs against `.agents/skills/polirium/data/*.csv`.
   - Validate all CSV files parse with the same column count as their headers.
   - Run representative searches: permission structure, controller pattern, livewire table, task module, purchase route.

2. Baseline automated checks.
   - `php artisan route:list`
   - `php artisan test`
   - `./vendor/bin/pint --test` when available.
   - `npm run production` or `yarn production` for FE assets.
   - Laravel logs: Boost `read_log_entries`, `last_error`.

3. Backend and logic audit.
   - For each module route group, trace route -> permission middleware -> controller -> request validation -> model/service -> database constraints -> response/view.
   - Check create/update/delete flows for validation, authorization, transactions, branch scoping, mass assignment, unique constraints, stock/accounting side effects, and events.
   - Prioritize modules that touch money, stock, customers, vendors, users, roles, and branch visibility.

4. Permission and employee audit.
   - Build permission matrix from config permissions, DB `permissions`, roles, `role_has_permissions`, user roles, route middleware, menu visibility, and `core_can()` or `@can` usage.
   - Query every user without exposing password, tokens, or secrets.
   - Check active users without branch scope, deleted users with active status, super admin without explicit role, orphan role assignments, permissions in DB but not config, config flags not seeded, and menus using nonexistent permission flags.
   - Verify representative roles by test login, actingAs tests, or browser login with a controlled local test user.

5. Frontend and Livewire audit.
   - Use Polirium docs: `platform/docs/20-ui-ux-design-system.md`, `platform/docs/30-ui-design-system.md`, `platform/docs/50-accessibility-guide.md`, `platform/docs/CSS-RULES.md`, `platform/docs/09-ui-components.md`, and `platform/docs/12-form-system.md`.
   - Use browser logs for JS/Livewire/Alpine errors.
   - Create or use a local controlled test user; assign one role or selected permissions per scenario.
   - Exercise login, menu visibility, access denied states, CRUD modals, validation errors, filters/search, pagination, import/export, Kanban/Gantt, stock/payment/vendor workflows.
   - Capture screenshots for UI defects and compare before/after.

6. Fix loop.
   - Fix one root cause at a time unless issues are coupled.
   - Re-run the failing check first.
   - Re-run the nearest regression suite.
   - Re-run broader checks when shared code, permissions, menu, auth, settings, or frontend assets changed.
   - Stop only when no known failing verification remains, or record a blocker with exact evidence.

## Initial Candidate Issues To Verify

These are not final bugs until reproduced through the evidence format above:

1. Settings decrypt log error: `The MAC is invalid` in `platform/core/settings/src/Settings.php` via `get_title()`/`get_logo()`.
2. Vendor menu permission mismatch: `platform/modules/vendor/config/menu.php` uses `vendors.view` and `transfers.view`, while vendor permissions config and DB roles use `vendors.index` and `vendors.transfers.view/index`.
3. Task permission mismatch: routes for `admin.tasks.kanban` and `admin.tasks.gantt` use `can:tasks.index`, while config defines `tasks.kanban` and `tasks.gantt`.
4. Employee branch scope: current read-only query showed active non-super-admin users with no active branch assignment.
5. Deleted employee status: user id 6 is soft-deleted but still has `status = active`.

## Completion Evidence Required

The audit/fix run is complete only after the final report lists:

- Fixed issues with evidence before and after.
- Verification commands and summarized outputs.
- Browser screenshots/log results for FE defects.
- Database queries used for employee/permission proof.
- Checks intentionally skipped and why.
- Residual risks.
