# Changelog

All notable changes to Polirium are documented here. Versions follow
[Semantic Versioning](https://semver.org/). Each release on GitHub carries a
self-contained package that the in-app updater installs.

## [1.0.0] - 2026-10-17

First versioned release.

### Added
- **In-app updates from GitHub releases** (Settings → System update, or
  `php artisan poli:update`): checks for new releases, downloads the release
  package, verifies its SHA-256 checksum and PHP requirements, backs up the
  database and the replaced code, migrates, and restores the previous code
  automatically if anything fails. A one-click rollback restores the last
  backed-up code. `.env`, `storage` and `public/uploads` are never touched.
- **Homepage setting** (Settings → System): show the public website on the
  domain root, or go straight to the admin.
- Release workflow: pushing a tag `vX.Y.Z` tests the code and publishes the
  package (code, submodules, production vendor, built assets).
- Safe web installer that never installs over a database already running
  Polirium.
- Task module: project and task pages with subtasks, comments, time logs,
  attachments, dependencies (cycle-checked), Kanban and a Gantt chart with
  hierarchy and dependency arrows; branch isolation and dedicated Kanban/Gantt
  permissions.
- Vietnamese validation messages for the whole application.

### Changed
- Upgraded to Laravel 13 and Livewire 4; assets are built with esbuild + Sass.
- Editing an invoice changes stock only by the difference; an unchanged
  invoice leaves the stock card untouched.
- Product import and opening stock are recorded on the stock card instead of
  overwriting quantities.
- Inventory report opening/closing balances come from recorded stock card
  balances; legacy gaps are shown explicitly.

### Fixed
- Products with stock, documents or combo usage can no longer be hard-deleted
  or change type; copies get a fresh code and identity, never stock or history.
- Combo components must be real goods with a quantity of at least 1.
- Datatable, date picker and select styles missing after the build tool change.
- Point of sale v2 page crash.
- Several Task module errors (empty numeric fields, missing validation and
  success messages, unescaped names).
