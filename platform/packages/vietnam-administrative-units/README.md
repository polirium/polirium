# Polirium Vietnamese Administrative Units

Local Laravel package containing Vietnam's official two-tier administrative
unit codes effective from 1 July 2025.

The bundled dataset contains 34 province-level units and 3,321 commune-level
units from Decision 19/2025/QD-TTg. District records are retained only for
backward compatibility with historical Polirium addresses.

```bash
# Preview changes without writing
php artisan vietnam-units:sync

# Apply official data and hide units that are no longer active
php artisan vietnam-units:sync --apply --archive-missing
```

The sync is transactional and idempotent. It never deletes address records.
See `resources/data/SOURCE.md` for provenance and update instructions.

After two-tier records have been synchronized, the schema migration deliberately
refuses to roll back while any ward has no legacy district. This prevents a
rollback from orphaning or deleting current address data; restore a compatible
database backup if a full downgrade is required.
