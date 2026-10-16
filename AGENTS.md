# Repository contribution rules

## Database migrations and production data

Polirium is a community repository. A migration runs in every installation, so it must never encode data that belongs to one deployment.

- Never hard-code tenant-specific identifiers or corrections in a migration. This includes product, invoice, stock-count, customer, vendor, branch, user, or other business record codes and IDs.
- Never use a migration to apply a physical stock count, repair one customer's records, or reproduce an operator's one-off SQL fix.
- Migrations may change schema and may perform only deterministic, installation-independent transformations that are required by the new schema or a global application invariant.
- A necessary deployment-specific repair belongs in an opt-in Artisan command or an external runbook. It must accept explicit inputs, default to dry-run, require confirmation before writing, be idempotent where practical, use a transaction, and report what changed.
- Do not commit real customer identifiers or production data to source control, fixtures, examples, tests, commit messages, or documentation. Use clearly fictional values.
- Before adding a data-writing migration, verify it against a fresh empty database and a representative upgraded database. Its `down()` method must reverse the change safely; if it cannot, document why the transformation is globally necessary and obtain maintainer review.

Reviewers must reject migrations containing deployment-specific business values even when guarded by `if (! $record) return` or a matching current quantity. A no-op on other installations does not make tenant-specific data suitable for a shared migration.
