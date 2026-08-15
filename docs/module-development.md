# Module Development

This guide is the working checklist for adding CMS modules.

## 1. Choose The Layer

- `platform/core`: required foundation modules.
- `platform/packages`: reusable CMS features.
- `platform/plugins`: optional features.
- `platform/themes`: public frontend themes.

## 2. Scaffold

```bash
php artisan module:make <type> <name>
```

Examples:

```bash
php artisan module:make core acl
php artisan module:make package page
php artisan module:make plugin blog
php artisan module:make theme default
```

Use `--force` only when intentionally regenerating a local module skeleton.

## 3. Verify Package Metadata

Each module should include:

- Composer package name with `sitewyn/*`.
- PSR-4 namespace with `Sitewyn`.
- Laravel provider in `extra.laravel.providers`.
- Frontend package name with `@sitewyn/*`.

Run:

```bash
composer validate --strict
composer dump-autoload
```

## 4. Keep Code In The Module

Put module-owned code in the module folder:

- Routes: `routes/`
- Controllers: `src/Http/Controllers/`
- Models: `src/Models/`
- Providers: `src/Providers/`
- Views: `resources/views/`
- Assets: `resources/css/` and `resources/js/`
- Migrations: `database/migrations/`

Avoid putting CMS module logic in `app/` when a platform module owns it.

## 5. Register Routes, Views, Config, And Migrations

Provider classes should load module resources:

```php
$this->mergeConfigFrom($this->modulePath('config/example.php'), 'example');
$this->loadViewsFrom($this->modulePath('resources/views'), 'plugin/example');
$this->loadRoutesFrom($this->modulePath('routes/web.php'));
$this->loadMigrationsFrom($this->modulePath('database/migrations'));
```

## 6. Plan Permissions And Menus

Before building admin UI, write down:

- Permission keys such as `posts.index`, `posts.create`, `posts.edit`.
- Admin route names such as `admin.posts.index`.
- Sidebar label, icon, route, and required permission.

Dedicated permission and menu registries will formalize these later.

## 7. Admin UI Rules

Admin screens must use Tabler conventions for layout, spacing, forms, tables,
tabs, dropdowns, cards, modals, and responsive behavior.

Before closing UI work, verify:

- Text does not overflow.
- Form errors are visible.
- Empty/loading states exist.
- Table actions are consistent.
- Mobile layout does not break.

## 8. Final Checks

Run:

```bash
composer test
npm run build
```

When the module changes Composer metadata, also run:

```bash
composer validate --strict
composer dump-autoload
```
