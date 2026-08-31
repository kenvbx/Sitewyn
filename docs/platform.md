# Platform Administration

The Platform Administration hub (`GET /admin/system`, route name
`admin.system`) is a Botble-style card grid that links every administration
tool in one place. It lives entirely inside the core base module:

```text
platform/core/base
├── src/Http/Controllers/Admin/PlatformAdminController.php
└── resources/views/admin/platform/index.blade.php
```

## Access

Like the Dashboard, the route carries only the `auth:admin` middleware —
**no `permission:` gate** — so every signed-in admin can open the hub. Each
card hides itself when the current user lacks its permission, mirroring the
`AdminMenuRegistry::allowed()` rule (`permission === null` means everyone).
The Users card is the exception: it is gated on **team membership** instead of
a permission (see below). An admin who is not a team member and holds none of
the listed permissions sees the empty state "No administration tools available
for your account."

## Sidebar

The sidebar item `system` ("Platform Administration", icon `shield`,
order 99 — last entry, after Settings 90) is registered in
`BaseServiceProvider::registerCoreAdminMenu()` with **no permission key**, so
the hub entry is visible to every admin just like the Dashboard entry.

## Cards

Card metadata is a static array in `PlatformAdminController`; the view only
renders what the controller passes. Cards use the shared icon partial
(`admin/partials/icon.blade.php`); the `shield`, `roles`, and `key` cases were
added for this page.

| Card                | Gate                | Links to            | Icon    |
|---------------------|---------------------|---------------------|---------|
| Users               | Team member         | `/admin/users`      | users   |
| Roles & Permissions | `roles.index`       | `/admin/roles`      | roles   |
| Permissions         | `permissions.index` | `/admin/permissions`| key     |
| Media               | `media.index`       | `/admin/media`      | media   |
| Menus               | `menus.manage`      | `/admin/menus`      | menu    |
| Widgets             | `widgets.manage`    | `/admin/widgets`    | widget  |
| Plugins             | `plugins.manage`    | `/admin/plugins`    | plugin  |
| Audit Logs          | `audit.index`       | `/admin/audit-logs` | audit   |
| Backups             | `backups.manage`    | `/admin/backups`    | backup  |
| Settings            | `settings.edit`     | `/admin/settings`   | settings|

## Team members and the Users card

"Team member" means a super admin **or** a user holding the built-in `Admin`
role (slug `admin`). The rule lives in
`PlatformAdminController::isTeamMember()`; `UserController::index()` applies
the same rule as a query scope.

- The **Users card** only renders for team members — a `users.index`
  permission alone is no longer enough.
- The **`/admin/users` index** lists *team members only*
  (`is_super_admin = true` OR a role with slug `admin`). User management is
  for managing the platform team: users outside the team never appear in the
  list, so they cannot be found, edited or deleted through this UI (search
  and status filtering operate on the already-scoped list).
- **Creating users still works** for anyone with `users.create`. Whether a
  newly created user becomes part of the team depends on what is assigned in
  the form: grant the `Admin` role (or the super admin flag) to have them
  show up in the index afterwards — a user saved without either will not be
  listed on `/admin/users`.
- The built-in `Admin` role is created idempotently (`firstOrCreate`) by
  `SuperAdminSeeder` on every `db:seed` run. It deliberately ships with **no
  permissions** — super admins assign them through the Roles UI.

## Adding a new card

Append one entry to the `CARDS` constant in `PlatformAdminController` —
position in the array is the display order:

```php
['title' => 'Request Logs', 'description' => 'Inspect recent HTTP requests handled by the platform.', 'icon' => 'audit', 'url' => '/admin/request-logs', 'permission' => 'request-logs.index'],
```

Rules of thumb:

- `permission` must be a key registered in the `PermissionRegistry` (or
  `null` to show the card to every admin). Cards may instead set
  `'team' => true` to gate on team membership (Users precedent) — team-gated
  cards ignore `permission`.
- If no icon case matches in `admin/partials/icon.blade.php`, add one
  (Tabler outline, 24×24, stroke-based) and mirror it in the `iconPaths`
  whitelist inside `admin/layouts/master.blade.php` — the global search
  palette reads its icons from there.
- Cards never run queries; the controller stays query-free so the hub stays
  cheap no matter how many modules register tools.

Feature tests: `tests/Feature/AdminPlatformTest.php`.
