# Users: team surface & outside surface

User management is split into two surfaces over the same `users` table. The
membership rule lives in one place: `App\Models\User::isTeamMember()` — a
**team member** is a super admin *or* a holder of the built-in `Admin` role
(slug `admin`, seeded by `SuperAdminSeeder`).

|                 | Team surface                              | Outside surface                    |
|-----------------|-------------------------------------------|------------------------------------|
| URL             | `/admin/system/users`                     | `/admin/users`                     |
| Route names     | `admin.system.users.*`                    | `admin.users.*`                    |
| Controller      | `SystemUserController`                    | `UserController`                   |
| Permissions     | `system.users.index/create/edit/delete`   | `users.index/create/edit/delete`   |
| Lists           | team members only (`isTeamMember()` match)| outside users only (NOT `isTeamMember()`) |
| Form fields     | name, username, email, password, is_active, **roles**, **is_super_admin** | name, username, email, password, is_active |
| Views           | `core/base::admin.system-users.*`         | `core/base::admin.users.*`         |
| Hub card        | "Users" (team-gated)                      | "Members" (`users.index`)          |

## Rules

- **Privileges only move on the team surface.** The outside requests
  (`StoreUserRequest` / `UpdateUserRequest`) do not validate `roles` or
  `is_super_admin`, and the outside controller never writes them — a payload
  containing those fields is silently ignored. Users outside the team can
  never grant themselves admin privileges, so the outside surface has no
  escalation guards by construction.
- **Escalation guards live in `SystemUserController`** (moved verbatim from
  the pre-split `UserController`):
  - self-edits strip `roles` and `is_super_admin` (nobody changes their own
    privileges, not even super admins — self `is_active` is forced on);
  - only super admins may grant the super admin flag;
  - non-super admins may only assign roles whose permissions are a subset of
    their own (the role list on the form is filtered the same way).
- **Cross-surface guards.** Editing or deleting a *team member* through the
  outside surface returns 404 (`UserController::assertOutsideUser()`) —
  team accounts are managed only under Platform Administration. The team
  surface keeps no such guard: its index lists team members, but a super
  admin may still open `/admin/system/users/{outside-user}/edit` by URL and
  promote an outside user (grant the `Admin` role or the super flag). After
  promotion the account disappears from `/admin/users` and the outside
  surface starts returning 404 for it.
- **Deleting yourself is impossible on both surfaces** (friendly flash error,
  delete button disabled for the own row).
- **Dashboard stat "Users"** counts *all* accounts — both surfaces combined.

## Search routing

`SearchController` routes each user hit to the surface that can edit the
target: `User::isTeamMember()` → `/admin/system/users/{id}/edit`, otherwise
`/admin/users/{id}/edit`. The group stays gated on `users.index`.

## Adding permissions

`system.users.*` keys are registered in
`BaseServiceProvider::registerCorePermissions()` under the group
`system users` (module `core/base`) and are synced to the `permissions` table
by `php artisan permission:sync` (39 keys total as of this split).

## Tests

- `tests/Feature/AdminUserCrudTest.php` — outside surface CRUD, payload
  ignoring privilege fields, cross-surface 404s, self-delete guard.
- `tests/Feature/AdminSystemUsersTest.php` — team surface CRUD, list
  scoping, cross-list visibility, self-delete guard.
- `tests/Feature/AdminUserEscalationTest.php` — escalation guards against
  the team surface.
- `tests/Feature/AdminPlatformTest.php` — hub card gating and links.
- `tests/Feature/AdminSearchTest.php` — search link routing per surface.
