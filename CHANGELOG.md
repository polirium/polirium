# Changelog

All notable changes to Polirium are documented here. Versions follow
[Semantic Versioning](https://semver.org/). Each release on GitHub carries a
self-contained package that the in-app updater installs.

## [1.1.1] - 2026-10-20

### Security
- laravel/framework 13.37.0 ([GHSA-f9x3-7vq9-cph6](https://github.com/laravel/framework/security/advisories/GHSA-f9x3-7vq9-cph6),
  token guard authentication bypass). Polirium only uses the session and
  Sanctum guards and was not exploitable, but the affected code is
  replaced. Also phpseclib 3.0.57 and symfony/yaml 7.4.21; `composer
  audit` reports no advisories.

### Fixed
- Excel downloads of a purchase return, a purchase and an invoice failed
  with an error 500.
- Point of sale: saving, saving as draft and opening a draft check the
  sales permission and the branch; the opened draft is kept on the server,
  so a crafted request can no longer overwrite or complete another
  branch's draft. The unused `completeDraft` action is removed (drafts are
  completed through the normal payment). The draft list shows only drafts
  of the user's branches.
- Invoice page, export, copy and print, and the invoice edit dialog, open
  only invoices of a branch the user works in.
- Returns from the point of sale need the "Hoàn tiền" permission
  (`sales.payment.refund`), the same as the return page, instead of
  product editing; the Return button is hidden without it.
- Roles: the delete button works; a role still assigned to users is not
  deleted.
- Invoice and purchase lists no longer mount a dialog in every expanded
  row; the customer list, invoice page and purchase page mount it once.
  The product button on the purchase page opens the product again.
- Sales dashboard widgets add up in the database instead of loading every
  sale of the period.

## [1.1.0] - 2026-10-19

### Added
- Point of sale: reworked screen. Enter adds the exact code or the
  highlighted result (barcode friendly), arrows move through results, F3
  focuses search and F9 pays; the amount due and the Pay button stay
  visible; scanning a product again adds to its quantity.
- Point of sale: open invoices and returns are kept on the server, so a
  reload, a closed browser or a new session brings every tab back.
- Header search (Ctrl+K) across products, customers, invoices, suppliers,
  projects and tasks, limited to what the user may see in their branch.
- Dashboard: widgets on a grid that can be dragged and resized; widgets
  load after the page.
- Price list: selling prices edited in place.
- `refunds:convert-money`: optional command that rewrites POS returns
  saved before this version in VND (dry run unless `--apply`).
- `demo:data`: multi-branch sample data posted through the application
  itself (only on installations marked `POLIRIUM_DEMO=true`).

### Changed
- Modals open the moment a button is clicked and show a spinner while
  their content loads; fades are shorter and the page behind is no longer
  blurred.
- Consistent row actions, toolbars and modal footers on every module
  screen; compact tables with a 300 ms search delay.
- Faster lists: indexes for list filters and code look-ups, the product
  list loads once, the stock card in the product detail loads in a few
  queries.

### Fixed
- Point of sale could record the same sale, draft or return twice (double
  click, F9 plus click, resubmit or a second tab), posting stock twice.
  Each checkout now has a key; completing a draft locks it first; the cash
  receipt is written in the sale's transaction.
- Document codes (BH, PT, TRH…) could repeat when two tills saved at once;
  they now come from a locked counter.
- Returns could exceed what was sold, counted earlier returns only on one
  of the two return screens, and put stock back in the selected branch
  instead of the sale's branch.
- POS returns showed the original total divided by 100 (a 1,288,000 sale
  as "12,880") and mixed units between screens; all amounts are VND.
  Returns saved before this version still display correctly.
- Amounts typed as "20,000" in a return were read as 20.
- A draft opened into every open tab, and a new customer was set on every
  invoice.
- "Đơn tạm" (drafts) appeared only after the server answered and loaded
  the list on every POS refresh; its pagination did not work.
- Modals could open twice and leave a dark backdrop behind after closing.
- Dashboard widgets: revenue by payment method was always 0, cash flow
  receipts were always 0, "last 7/30 days" meant this week/month.
- `audit:inventory` checked stock cards across branches as one chain and
  `--fix` rewrote history; it now checks per branch and only aligns
  current quantities.
- Invoice detail panel, the expanded row of the return list and the
  product price list failed to open or lost their actions.
- Saved dashboard layouts were deleted when a permission was revoked or a
  module disabled; they are now kept.
- Returns pre-filled the list price, ignoring line and invoice discounts, so
  a full return refunded more than the customer paid. They now default to
  what was actually paid.
- A return could be made against a draft or cancelled invoice, adding stock
  that never left; such invoices are refused.
- Deleting a draft did not lock it, so a draft being completed at the same
  moment could lose its lines; it now needs POS access and is locked.
- Revenue and sales widgets counted held (draft) invoices as sales.
- The first document code of a prefix could fail when two tills saved at
  the same moment (database deadlock).
- Return codes on the invoice page opened an unrelated invoice and the
  print button printed one; they now open the return in the return list.

### Changed (commands)
- `audit:inventory --fix` asks for confirmation (pass `--force` in
  scripts), removes orphan records in one transaction and creates missing
  branch stock rows instead of reporting them fixed.
- `demo:data` runs only on installations marked `POLIRIUM_DEMO=true`,
  with or without `--fresh`.

### Security
- The user form no longer writes edited user details to the log.
- Dashboard: the widget list and layout could be changed from the browser to
  show widgets the user may not see. They are now locked and checked on the
  server, and every widget checks its own permissions.
- Dashboard: the revenue, sales summary and cash book widgets showed figures
  from every branch; they now follow the selected branch like the rest of
  the application.
- Tables with relation search (for example purchases) could return rows from
  other branches when searching; matches now stay inside the table's filters.

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
