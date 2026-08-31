# Platform Administration

The Platform Administration hub (`GET /admin/platform`, route name
`admin.platform`) is a Botble-style card grid that links every administration
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
An admin whose role holds none of the listed permissions sees the empty state
"No administration tools available for your account."

## Sidebar

The sidebar item `platform` ("Platform Administration", icon `shield`,
order 89 — between Backups 87 and Settings 90) is registered in
`BaseServiceProvider::registerCoreAdminMenu()` with **no permission key**, so
the hub entry is visible to every admin just like the Dashboard entry.

## Cards

Card metadata is a static array in `PlatformAdminController`; the view only
renders what the controller passes. Cards use the shared icon partial
(`admin/partials/icon.blade.php`); the `shield`, `roles`, and `key` cases were
added for this page.

| Card                | Permission          | Links to            | Icon    |
|---------------------|---------------------|---------------------|---------|
| Users               | `users.index`       | `/admin/users`      | users   |
| Roles & Permissions | `roles.index`       | `/admin/roles`      | roles   |
| Permissions         | `permissions.index` | `/admin/permissions`| key     |
| Media               | `media.index`       | `/admin/media`      | media   |
| Menus               | `menus.manage`      | `/admin/menus`      | menu    |
| Widgets             | `widgets.manage`    | `/admin/widgets`    | widget  |
| Plugins             | `plugins.manage`    | `/admin/plugins`    | plugin  |
| Audit Logs          | `audit.index`       | `/admin/audit-logs` | audit   |
| Backups             | `backups.manage`    | `/admin/backups`    | backup  |
| Settings            | `settings.edit`     | `/admin/settings`   | settings|

## Adding a new card

Append one entry to the `CARDS` constant in `PlatformAdminController` —
position in the array is the display order:

```php
['title' => 'Request Logs', 'description' => 'Inspect recent HTTP requests handled by the platform.', 'icon' => 'audit', 'url' => '/admin/request-logs', 'permission' => 'request-logs.index'],
```

Rules of thumb:

- `permission` must be a key registered in the `PermissionRegistry` (or
  `null` to show the card to every admin).
- If no icon case matches in `admin/partials/icon.blade.php`, add one
  (Tabler outline, 24×24, stroke-based) and mirror it in the `iconPaths`
  whitelist inside `admin/layouts/master.blade.php` — the global search
  palette reads its icons from there.
- Cards never run queries; the controller stays query-free so the hub stays
  cheap no matter how many modules register tools.

Feature tests: `tests/Feature/AdminPlatformTest.php`.
