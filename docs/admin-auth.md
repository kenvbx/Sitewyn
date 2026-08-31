# Admin Authentication

P1-04 adds the first admin authentication flow.

## Routes

- `GET /admin/login`: show the admin login form.
- `POST /admin/login`: authenticate an active admin account.
- `GET /admin/forgot-password`: show the admin password reset request form.
- `POST /admin/forgot-password`: email the admin reset link.
- `GET /admin/reset-password/{token}`: show the reset form.
- `POST /admin/reset-password`: reset the password and return to login.
- `GET /admin`: protected admin dashboard placeholder.
- `POST /admin/logout`: end the admin session.

All admin auth routes are loaded from `platform/core/base/routes/web.php` and use the `web` middleware group for sessions, CSRF, old input, and validation errors.

## Auth Rate Limiting

Admin auth endpoints are protected by Laravel `RateLimiter` counters applied in
the controllers/requests (no `throttle` middleware, so users get the friendly
redirect-back validation error instead of a raw 429 page):

- `POST /admin/login`: max 5 failed attempts per `sha1(email|ip)` per 60 seconds.
  The check runs in `AdminLoginRequest::ensureIsNotRateLimited()` before
  authenticating, failed passwords call `hitRateLimit()`, and a successful login
  calls `clearRateLimit()`. A correct password is still rejected while the pair
  is locked out. The message is
  `Too many login attempts. Please try again in :seconds seconds.`
- `POST /admin/forgot-password`: max 5 requests per `sha1(email|ip)` per 60
  seconds; every request (successful or not) counts, which caps mail bombing per
  mailbox. `GET /admin/forgot-password` is not throttled.
- `POST /admin/reset-password`: max 10 attempts per `sha1(ip)` per 60 seconds to
  slow token brute force. `GET /admin/reset-password/{token}` is not throttled.

Counters live in the default cache store; use a durable store (redis, database)
in production so they survive worker restarts.

## Guard

The admin area uses the `admin` session guard from `config/auth.php`.

The guard currently shares the default `users` provider. Admin access is controlled by user state:

- `is_active = true`
- valid email/password

Later P1 tasks will add permission middleware and role-based access checks.

## Password Reset

The admin reset flow uses Laravel's default `users` password broker and `password_reset_tokens` table.

`BaseServiceProvider` customizes Laravel's reset URL so reset emails point to the admin route:

```text
/admin/reset-password/{token}?email={email}
```

## Views

Admin auth views live in the core base module:

- `platform/core/base/resources/views/admin/auth/login.blade.php`
- `platform/core/base/resources/views/admin/auth/forgot-password.blade.php`
- `platform/core/base/resources/views/admin/auth/reset-password.blade.php`
- `platform/core/base/resources/views/admin/dashboard.blade.php`

The views load Tabler through the module admin Vite entries:

```blade
@vite(['platform/core/base/resources/css/admin.css', 'platform/core/base/resources/js/admin.js'])
```

## Admin Layout

P1-12 adds the shared admin layout at:

```text
platform/core/base/resources/views/admin/layouts/master.blade.php
```

CRUD-style admin pages should extend:

```blade
@extends('core/base::admin.layouts.master')
```

The master layout owns the Tabler sidebar, topbar, page header, breadcrumb
region, flash messages, and content container. It exposes these sections:

- `title`
- `pretitle`
- `page-title`
- `breadcrumbs`
- `page-actions`
- `content`

`core/base::admin.layouts.app` remains as a thin compatibility alias for pages
created before P1-12. New pages should use `master` directly.

## Admin Components

P1-14 adds reusable Tabler Blade components for admin modules:

- `<x-admin-card>`
- `<x-admin-data-table>`
- `<x-admin-form-group>`
- `<x-admin-modal>`
- `<x-admin-alert>`
- `<x-admin-toast>`
- `<x-admin-pagination>`

Example:

```blade
<x-admin-card title="Role information">
    <x-admin-form-group name="name" label="Name" :value="$role->name" required />

    <x-slot:footer>
        <div class="text-end">
            <button type="submit" class="btn btn-primary">Save role</button>
        </div>
    </x-slot:footer>
</x-admin-card>
```

The component views keep Tabler's native HTML classes (`card`,
`table-responsive`, `form-control`, `modal`, `alert`, `toast`, and pagination
wrappers), so module pages should prefer these components instead of hand
rolling repeated admin markup.

P1-15 extends `<x-admin-data-table>` with Tabler's `datatables.html` pattern,
powered by `list.js` copied from the local Tabler source:

```blade
<x-admin-data-table
    id="admin-posts-table"
    title="Post list"
    :value-names="['sort-title', 'sort-status', ['name' => 'sort-date', 'attr' => 'data-date']]"
    searchable
    paginated
    :page="10"
>
    <x-slot:head>
        <tr>
            <th><button class="table-sort" data-sort="sort-title">Title</button></th>
            <th><button class="table-sort" data-sort="sort-status">Status</button></th>
            <th><button class="table-sort" data-sort="sort-date">Date</button></th>
        </tr>
    </x-slot:head>

    <tr>
        <td class="sort-title">About</td>
        <td class="sort-status">Published</td>
        <td class="sort-date" data-date="1723680000">2024-08-15</td>
    </tr>
</x-admin-data-table>
```

Current Users/Roles tables use client-side search, sort, and pagination for the
MVP. Move high-volume resources to server-side mode later.

P1-16 adds the `admin_flash()` helper for CRUD feedback:

```php
admin_flash()->success(__('User created successfully.'));
admin_flash()->error(__('Cannot delete a role that has users.'));
admin_flash()->warning(__('Please review the form.'));
admin_flash()->info(__('Settings were already up to date.'));
```

The helper stores a normalized `admin_flash` payload and also keeps the legacy
`status`/`error` session keys for compatibility. The master admin layout renders
flash feedback as a Tabler toast using the local `x-admin-toast` component and
the `toast-container position-fixed bottom-0 end-0 p-3` structure from Tabler's
toast preview.

P1-17 adds a shared validation pattern for admin forms. Server-side validation
continues to live in Laravel FormRequest classes under:

```text
platform/core/base/src/Http/Requests/Admin
```

Client-side validation uses Tabler's Bootstrap markup: forms add
`needs-validation`, `data-admin-validate`, and `novalidate`; fields use native
HTML attributes rendered by `<x-admin-form-group>`.

```blade
<form method="POST" class="needs-validation" data-admin-validate novalidate>
    <x-admin-form-group
        name="slug"
        label="Slug"
        :maxlength="255"
        pattern="[A-Za-z0-9_-]+"
        invalid-feedback="Slug may only contain letters, numbers, dashes, and underscores."
    />
</form>
```

The master admin layout adds `was-validated` on submit and supports password
confirmation style checks through `data-admin-confirm`.

P1-19 adds minimal general settings:

- `GET /admin/settings`
- `PUT /admin/settings`
- required permission: `settings.edit`

Settings are stored as key-value rows in the `settings` table and read through
`Sitewyn\Core\Base\Support\SettingStore`. Values are cached under
`sitewyn.settings`; saving settings clears that cache and reapplies
`config('app.name')` from `site_name`.

```php
site_setting('site_name', config('app.name'));
site_setting('site_logo');
```

The first editable keys are `site_name` and `site_logo`. The admin form uses the
same Tabler card/form-group validation pattern as Users and Roles.

P5-02 adds the `active_theme` key to the same form (select of the themes
discovered by `ThemeManager` under `platform/themes/*`; validated against the
available slugs). The active theme owns the public frontend views — see
`docs/themes.md`.

P5-08 adds the `robots_txt` key to the same form (textarea, max 2000 chars) and
two public, unauthenticated SEO file routes served from core/base:

- `GET /robots.txt` (`robots.txt`, `text/plain; charset=UTF-8`): returns the
  saved `robots_txt` setting as-is; blank or unset falls back to
  `User-agent: *\nDisallow: /admin\n` (`RobotsTxt::content()`), and clearing the
  textarea in the form restores that default. No throttling, no session.
- `GET /sitemap.xml` (`sitemap`, `application/xml; charset=UTF-8`): always
  responds 200 with a valid sitemap 0.9 `<urlset>`, even when nothing is
  published. Entries come from `Sitewyn\Core\Base\Support\SitemapRegistry` — a
  singleton like `AdminMenuRegistry` where modules `register()` callables that
  return `['loc' => absolute URL, 'lastmod' => \DateTimeInterface|null]`; the
  controller invokes every contributor per request, dedupes by `loc`, and core
  never queries page/post repositories itself. The page package contributes
  published pages (`/{slug}`) and the blog package published posts
  (`/blog/{slug}`) with `updated_at` as `lastmod`, so newly published content
  appears on the next sitemap request with no cache to clear.

Because core/base routes are registered before every package route, both
file names are matched before the page catch-all `/{slug}`; the catch-all
additionally excludes `sitemap.xml` and `robots.txt` as a second line of
defense (`SeoFilesTest` covers the dispute case where content is named exactly
like the files).

P1-20 adds focused access-control coverage for the admin area. The suite checks
that guests are redirected to `/admin/login`, regular admins without route
permissions receive 403 responses, admins with the required role permission can
open protected pages, super admins bypass route permissions, and bad login
passwords keep the admin guard unauthenticated.

## Admin Menu

P1-13 adds `Sitewyn\Core\Base\Support\AdminMenuRegistry` for dynamic sidebar
items. Modules should register admin menu entries from their service provider:

```php
use Sitewyn\Core\Base\Support\AdminMenuRegistry;

$this->app->make(AdminMenuRegistry::class)->register([
    [
        'id' => 'posts',
        'title' => 'Posts',
        'route' => 'admin.posts.index',
        'permission' => 'posts.index',
        'icon' => 'circle',
        'active' => ['admin.posts.*'],
        'order' => 40,
    ],
]);
```

Menu items support nested `children`, `permission`, `route`, `url`, `active`,
`icon`, and `order`. The master layout renders only items visible to the current
admin user, using the same permission helpers and super admin bypass as the rest
of the ACL layer.

## Verification

Run:

```bash
composer test
npm run build
php artisan route:list --path=admin --except-vendor
```
