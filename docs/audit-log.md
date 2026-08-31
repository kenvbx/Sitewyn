# Audit Log

P5-06 adds an audit/activity log for important admin actions: every create, update, and delete on tracked models is recorded with the acting user, and authentication events (login, logout, failed attempts) are recorded as well.

## Goals

- Record who did what, to which record, when, and from where.
- Never break the action being audited: logging failures are reported and swallowed.
- Never store secrets: sensitive keys are stripped before writing.
- Keep entries immutable: audit rows are created once and never revised (no `updated_at`).

## Table: audit_logs

| Column | Type | Notes |
| --- | --- | --- |
| `id` | bigint PK | |
| `user_id` | FK users, nullable | Null for actions without an authenticated admin (CLI, failed logins). `nullOnDelete`. |
| `action` | string(64) | `created`, `updated`, `deleted`, `login`, `logout`, `login-failed`. |
| `subject_type` | string(191) | Morph class of the affected model. |
| `subject_id` | unsigned bigint, nullable | Null for failed login attempts. |
| `properties` | json, nullable | Attributes or changed fields, sanitized. |
| `ip_address` | string(45), nullable | From the current request; null in CLI. |
| `user_agent` | string(500), nullable | Truncated to 500 chars; null in CLI. |
| `created_at` | timestamp, nullable | Only timestamp column — entries are append-only. |

Indexes: `(subject_type, subject_id)`, `user_id`, `action`, `created_at`.

## How entries are written

### Observer (create / update / delete)

`Sitewyn\Core\Base\Observers\AuditObserver` listens to `created`, `updated`, and `deleted` and writes through the `AuditLogger` singleton:

- `created`: all raw attributes of the new row (minus sensitive keys).
- `updated`: **only the changed fields** (`getChanges()`, without `created_at`/`updated_at` churn) plus the subject `id`.
- `deleted`: the old attributes captured at delete time.

Each package attaches the observer to its own models in its service provider, so core never references package models:

- `PageServiceProvider` → `Page`
- `BlogServiceProvider` → `Post`, `Category`, `Tag`
- `app/Providers/AppServiceProvider.php` → `User` (the app layer owns the model)

Media models are deliberately not tracked: uploads generate too much noise. Attach `AuditObserver` in the owning provider to track more models later.

### Authentication events

`BaseServiceProvider` listens to Laravel auth events for the `admin` guard only:

- `Login` → `login` with the user as subject.
- `Logout` → `logout` with the user as subject.
- `Failed` → `login-failed` with no subject and the attempted `email` in properties. The admin login controller verifies credentials manually instead of `Auth::attempt()`, so `AuthController::login()` fires the `Failed` event itself on a credential mismatch.

## Sanitization

Before writing, `AuditLogger` removes sensitive property keys — case-insensitively — at the top level and one nested level down:

- `password`
- `password_confirmation`
- `remember_token`

Everything else is logged as plain JSON (no encryption).

## Admin UI

`/admin/audit-logs` (`admin.audit-logs.index`) lists entries newest first, guarded by the `audit.index` permission (group `audit`, module `core/base`, menu item "Audit Logs").

- Always paginated server-side at **50 rows per page** via the `x-admin-pagination` component (rendered with the Bootstrap pagination view to match Tabler).
- Columns: time, user (`—` when deleted/null), action badge, subject (`Page #12` style), IP address.
- Filter dropdown by action; the options are the distinct actions present in the table, and an unknown value is ignored.
- "Details" opens a per-row modal with the entry metadata and the pretty-printed `properties` JSON.

## Tests

- `tests/Feature/AdminAuditLogTest.php`: CRUD logging for pages, login/logout/login-failed entries, sanitization, pagination (60 logs → 2 pages), action filter, permission gate, guest redirect, and the sidebar item.
- Ripple updates: `PermissionRegistryTest`, `AdminMediaPermissionTest` (31 → 32 permissions), `AdminMenuRegistryTest` (new `audit-logs` item).
