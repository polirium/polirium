# Changelog

All notable changes to Polirium are documented here. Versions follow
[Semantic Versioning](https://semver.org/). Each release on GitHub carries a
self-contained package that the in-app updater installs.

## [1.0.5] - unreleased

### Security
- Dashboard: the widget list and layout could be changed from the browser to
  show widgets the user may not see. They are now locked and checked on the
  server, and every widget checks its own permissions.
- Dashboard: the revenue, sales summary and cash book widgets showed figures
  from every branch; they now follow the selected branch like the rest of
  the application.
- Tables with relation search (for example purchases) could return rows from
  other branches when searching; matches now stay inside the table's filters.

### Fixed
- Saved dashboard layouts were deleted when a permission was revoked or a
  module disabled; they are now kept.

## [1.0.4] - 2026-10-17

### Fixed
- Settings saved before encryption was enabled (for example the store name
  and logo) logged "The payload is invalid" on every page view.

## [1.0.3] - 2026-10-17

### Fixed
- In-app updates refused to run when the host had made a file read-only
  (for example `public/index.php`, mode 0444). Files are swapped by renaming,
  which needs only a writable folder; the replaced file now keeps its
  original permissions.

## [1.0.2] - 2026-10-17

### Fixed
- In-app updates failed with "checksum mismatch": the updater asked GitHub
  for the package with a merged Accept header and received the asset's JSON
  description instead of the file. Installations on 1.0.0 or 1.0.1 need this
  one file (`app/Updater/ReleaseClient.php`) replaced by hand once, or a
  manual update, before the in-app updater works.

## [1.0.1] - 2026-10-17

### Changed
- New public homepage: a modern, responsive page (light and dark) that shows
  the store's own name and logo, only the modules that are enabled, and quick
  links that respect the signed-in user's permissions. It keeps working when
  modules are disabled or the database is unavailable.

### Fixed
- A freshly unzipped release package answered every page with a 500: the
  application now creates `.env` with a unique key on first run and boots
  without a database, so the web installer opens.
- Web installer: the database step now uses the database just entered within
  the same request, and the administrator form no longer comes pre-filled
  with the database username and password.

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
