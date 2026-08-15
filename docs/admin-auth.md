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

## Verification

Run:

```bash
composer test
npm run build
php artisan route:list --path=admin --except-vendor
```
