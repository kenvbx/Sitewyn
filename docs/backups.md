# Backups

P5-05 adds backup & restore: each backup is a single ZIP archive combining a JSON dump of the database with a mirror of the media files, created, downloaded, restored, and deleted from the admin UI.

## What a backup contains

| Archive entry | Content |
| --- | --- |
| `database.json` | Data-only dump: `{ "table": [rows...] }` for every user table (via `Schema::getTableListing()`, excluding `migrations`). |
| `files/...` | Full mirror of the media disk (`config('media.disk')`, fallback `public`) — media files only. |

Explicitly **not** included:

- **Schema** — the structure always comes from the current migrations. This is not an SQL dump; restoring assumes the schema of the code version doing the restore.
- Code, `.env`, and previous backups — backups live on the private `local` disk (`storage/app/backups`), which is itself never part of the mirrored media disk, so backups can never become publicly downloadable.

## Service

`Sitewyn\Core\Base\Support\BackupService` (singleton in `BaseServiceProvider`):

- `create(): string` — builds the archive with `ZipArchive` and returns the file name (`backup-2026-08-30-184500.zip`; same-second collisions get an incrementing suffix instead of overwriting).
- `list(): array` — `backup-*.zip` files in `backups/` as `name`, `sizeBytes`, `createdAt`, newest first.
- `download(string $name): string` — validates the name and returns the absolute path; the controller streams it with `response()->download()`.
- `delete(string $name): void` — validates the name and removes the archive.
- `restore(string $name): void` — full snapshot rollback (see below).

Invalid or missing names throw `InvalidArgumentException` (mapped to HTTP 404 by the controller); unreadable or corrupt archives throw `RuntimeException` (surfaced as a danger flash, never a crash).

## Restore behavior

1. `database.json` is read from the archive.
2. Foreign-key checks are disabled, driver-aware: MySQL/MariaDB `SET FOREIGN_KEY_CHECKS=0`; SQLite `PRAGMA foreign_keys = OFF` plus `PRAGMA defer_foreign_keys = ON` (the deferred pragma is transaction-safe, which also covers restores run inside an outer transaction such as tests).
3. Every dump table that exists in the current database is **truncated** and its rows re-inserted in chunks of 100. Rows are raw `DB::table()` data — Eloquent model events do not fire during restore.
   - A table present in the dump but missing from the current schema is **skipped** with a logged warning (schema drift, e.g. a table from an older version).
   - A table in the current database but absent from the dump is **left untouched**.
4. Foreign-key checks are re-enabled.
5. The media disk is emptied (all directories and files deleted) and repopulated from `files/...` in the archive — restore is a complete snapshot, media added after the backup is gone.

The archive entries are path-validated (no `..`/empty segments — zip-slip protection); unsafe entries are skipped with a warning.

**Restore is destructive and cannot be undone.** The UI confirms with a strong warning; create a fresh backup if the current state must be kept. Because the operation rewrites live tables, run it with the site in maintenance or with no concurrent traffic when possible.

## Admin UI

`/admin/backups` (`admin.backups.index`, menu item "Backups", order 87) lists archives with human-readable size and creation time, and offers:

- **Create backup** (POST `admin.backups.create`)
- **Download** (GET `admin.backups.download`) — streams the ZIP
- **Restore** (POST `admin.backups.restore`) — destructive-confirm modal
- **Delete** (POST `admin.backups.delete`) — confirm modal

Every action ends with an `admin_flash` message; failures (corrupt zip, unexpected file) flash a danger message and redirect instead of crashing.

## Security

- All five routes are gated by the `backups.manage` permission (group `backups`, module `core/base`) via the `permission:` middleware.
- File names must match `^backup-[A-Za-z0-9_-]+\.zip$` — anything else (including traversal attempts like `..%2F..%2F.env`) returns 404 before any filesystem access.
- Archives are stored on the `local` disk only — never in `public`, never URL-served.
- No database table is needed: the backup list is a directory scan, and the schema is never mutated by backup/restore.

## Tests

- `tests/Feature/AdminBackupTest.php`: archive contents (database.json + media mirror, no `migrations`), restore roundtrip (pages/posts/media return to the snapshot, post-backup drift removed), unknown/missing dump tables skipped, corrupt archive flashes an error, download streams the exact file, delete removes it, invalid names and traversal attempts 404, guest redirects, `backups.manage` 403s on all five routes, menu visibility.
- Ripple updates: `PermissionRegistryTest` (32 → 33 permissions, new `backups` group), `AdminMediaPermissionTest` (sync output 33), `AdminMenuRegistryTest` (`backups` item).
