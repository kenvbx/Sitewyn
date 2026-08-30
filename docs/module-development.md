# Module Development

This is the step-by-step guide for adding a CMS module.

## 1. Choose The Module Layer

Use the layer that matches the module's ownership:

- `platform/core`: required foundation modules.
- `platform/packages`: reusable CMS capabilities shared by many projects.
- `platform/plugins`: optional project features.
- `platform/themes`: public frontend presentation.

Examples:

```bash
php artisan module:make core acl
php artisan module:make package page
php artisan module:make plugin blog
php artisan module:make theme default
```

Use `--force` only when intentionally regenerating local scaffold files.

## 2. Scaffold The Module

Run:

```bash
php artisan module:make <type> <name>
```

The command normalizes the folder name to kebab case and creates:

```text
composer.json
package.json
config/<name>.php
routes/web.php
src/Providers/<StudlyName>ServiceProvider.php
resources/views/placeholder.blade.php
resources/css/admin.css
resources/js/admin.js
database/migrations/.gitkeep
```

For `php artisan module:make plugin blog`, the important generated identifiers
are:

```text
Folder: platform/plugins/blog
Composer package: sitewyn/plugin-blog
Frontend package: @sitewyn/plugin-blog
Namespace: Sitewyn\Plugins\Blog
View namespace: plugin/blog
Provider: Sitewyn\Plugins\Blog\Providers\BlogServiceProvider
```

## 3. Verify Registration

Each module registers itself through its own `composer.json`:

```json
{
    "autoload": {
        "psr-4": {
            "Sitewyn\\Plugins\\Blog\\": "src/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "Sitewyn\\Plugins\\Blog\\Providers\\BlogServiceProvider"
            ]
        }
    }
}
```

The core base provider scans module composer files under:

- `platform/core/*`
- `platform/packages/*`
- `platform/plugins/*`
- `platform/themes/*`

Do not add module providers manually to `bootstrap/providers.php`.

After changing module package metadata, run:

```bash
composer validate --strict
composer dump-autoload
```

## 4. Keep Code Inside The Module

Module-owned code should stay inside the module folder:

- Routes: `routes/`
- Controllers: `src/Http/Controllers/`
- Requests: `src/Http/Requests/`
- Models: `src/Models/`
- Providers: `src/Providers/`
- Views: `resources/views/`
- Assets: `resources/css/` and `resources/js/`
- Migrations: `database/migrations/`
- Tests: use project `tests/Feature` for now, named after the module behavior.

Avoid putting CMS module logic in `app/` unless it is truly project-level glue.

## 5. Load Module Resources

The generated service provider already contains the required loading pattern:

```php
public function register(): void
{
    $this->mergeConfigFrom($this->modulePath('config/blog.php'), 'blog');
}

public function boot(): void
{
    $this->loadViewsFrom($this->modulePath('resources/views'), 'plugin/blog');
    $this->loadRoutesFrom($this->modulePath('routes/web.php'));
    $this->loadMigrationsFrom($this->modulePath('database/migrations'));
}
```

Keep route names and view namespaces specific to the module.

## 6. Add Admin Routes

Admin routes should use the admin prefix, guard, route names, and permission
middleware already used by core admin screens:

```php
use Illuminate\Support\Facades\Route;
use Sitewyn\Plugins\Blog\Http\Controllers\Admin\PostController;

Route::prefix('admin')
    ->middleware(['web', 'auth:admin'])
    ->name('admin.')
    ->group(function (): void {
        Route::get('posts', [PostController::class, 'index'])
            ->middleware('permission:posts.index')
            ->name('posts.index');

        Route::get('posts/create', [PostController::class, 'create'])
            ->middleware('permission:posts.create')
            ->name('posts.create');

        Route::post('posts', [PostController::class, 'store'])
            ->middleware('permission:posts.create')
            ->name('posts.store');
    });
```

Use this route naming shape:

```text
admin.<resource>.<action>
```

Examples:

```text
admin.posts.index
admin.posts.create
admin.posts.edit
```

## 7. Register Permissions

Register admin permissions from the module provider:

```php
use Sitewyn\Core\Base\Support\PermissionRegistry;

private function registerPermissions(): void
{
    $this->app->make(PermissionRegistry::class)->register([
        [
            'key' => 'posts.index',
            'name' => 'View posts',
            'group' => 'posts',
            'description' => 'View post list.',
        ],
        [
            'key' => 'posts.create',
            'name' => 'Create posts',
            'group' => 'posts',
            'description' => 'Create new posts.',
        ],
        [
            'key' => 'posts.edit',
            'name' => 'Edit posts',
            'group' => 'posts',
            'description' => 'Edit existing posts.',
        ],
        [
            'key' => 'posts.delete',
            'name' => 'Delete posts',
            'group' => 'posts',
            'description' => 'Delete posts.',
        ],
    ], 'plugins/blog');
}
```

Call this method from `boot()` before admin pages need those permissions.

Sync registered permissions into the database with:

```bash
php artisan permission:sync
```

## 8. Register Admin Menu Items

Register sidebar items from the module provider:

```php
use Sitewyn\Core\Base\Support\AdminMenuRegistry;

private function registerAdminMenu(): void
{
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
}
```

Menu items support `children`, `permission`, `route`, `url`, `active`, `icon`,
and `order`. The admin layout hides menu items the current admin cannot access.

## 9. Build Admin Screens With Tabler

Admin pages must extend the shared master layout:

```blade
@extends('core/base::admin.layouts.master')
```

Use the existing Blade components before writing repeated markup:

```blade
<x-admin-card title="Post information">
    <x-admin-form-group
        name="title"
        label="Title"
        :value="$post->title"
        required
        :maxlength="255"
        invalid-feedback="Title is required."
    />

    <x-slot:footer>
        <div class="text-end">
            <button type="submit" class="btn btn-primary">Save post</button>
        </div>
    </x-slot:footer>
</x-admin-card>
```

For index pages, prefer `<x-admin-data-table>` when the list is small enough for
client-side search/sort/pagination. Move large resources to server-side
pagination later.

Admin HTML/CSS/JS must follow Tabler. When a screen needs a new pattern, copy or
port the matching source from:

```text
/Volumes/WORKSPACE/PROJECT/HTML/tabler-dev
```

Do not replace the shared admin layout with hand-written UI.

## 10. Validate Forms On Both Sides

Use FormRequest classes for server-side rules:

```php
class StorePostRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash:ascii'],
        ];
    }
}
```

Use the shared validation markup for client-side feedback:

```blade
<form method="POST" class="needs-validation" data-admin-validate novalidate>
    @csrf
    <x-admin-form-group
        name="slug"
        label="Slug"
        :maxlength="255"
        pattern="[A-Za-z0-9_-]+"
        invalid-feedback="Slug may only contain letters, numbers, dashes, and underscores."
    />
</form>
```

## 11. Store Settings Through SettingStore

For module settings, use module-owned keys and the cached setting helper:

```php
site_setting('blog.posts_per_page', '10');
```

For write flows, inject `Sitewyn\Core\Base\Support\SettingStore` and call
`setMany()`. Keep global settings such as `site_name` and `site_logo` in the
core settings page.

## 12. Add Tests

Minimum feature coverage for an admin module:

- Guest is redirected to `/admin/login`.
- Admin without permission receives 403.
- Admin with the required role permission can access the route.
- Super admin can access protected routes.
- FormRequest rejects invalid payloads.
- Create/update/delete flows persist the expected database changes.
- Admin views render the expected Tabler layout markers.

Run a focused test while building:

```bash
php artisan test --filter=AdminPostTest
```

Then run the full suite:

```bash
composer test
```

## 13. Update Documentation

Every new module should have enough documentation for the next developer to
continue safely:

- Purpose and scope.
- Routes and permissions.
- Database tables and models.
- Admin menu entries.
- Settings keys.
- Test commands.
- Known TODOs or limits.

Put module-specific docs in the module folder when the module grows beyond the
project-level roadmap.

## 14. Shared Admin Rich Text Editor

Use `<x-admin-editor name="content" label="Content" :height="480"
placeholder="...">` for admin rich text fields instead of a bare textarea. It
renders a hidden textarea with the `data-admin-editor` attributes; the admin
JS entry lazy-loads TinyMCE and replaces the textarea on the client.

Media and file picking is decoupled from the media package through a custom
event contract on `document` (`admin:editor-file-picker`, with
`detail = {callback, filetype, handled}`):

- The editor dispatches the event when the user opens the file picker.
- A listener that opens its own picker must set `detail.handled = true`
  synchronously and later call `detail.callback(url, meta)`.
- If no listener claims the event, the editor falls back to a URL prompt, so
  the component keeps working without the media package.

Core never imports the media package; the media picker component ships the
matching listener. TinyMCE skins are served from `public/vendor/tinymce/skins`
— copy them from `node_modules/tinymce/skins` after upgrading the npm package.

## 15. Final Checklist

Before closing the task:

- Module lives in the correct `platform/` layer.
- Composer package metadata is valid.
- Provider registration stays in module `composer.json`.
- Routes, views, config, migrations, and assets stay inside the module.
- Permissions are registered through `PermissionRegistry`.
- Sidebar items are registered through `AdminMenuRegistry`.
- Admin screens use the shared Tabler layout/components.
- Client-side validation and FormRequest validation both exist where needed.
- `php artisan permission:sync` works when permissions changed.
- `composer test` passes.
- `npm run build` passes when assets changed.
