# Contributing

## Commit Message Convention

Use short, focused commit messages:

```text
chore: initialize project repository
feat: add admin layout
fix: repair test script
docs: update module guide
test: add smoke coverage
```

Rules:

- Use English, imperative wording.
- Keep the subject under 72 characters when possible.
- Avoid reference product names and assistant/tool names.
- Do not mix unrelated changes in one commit.

## Before Committing

Run:

```bash
composer test
npm run build
```

For admin UI work, also verify the screen visually and keep Tabler layout intact.
Admin HTML/CSS/JS must be copied or ported from the local Tabler source at
`/Volumes/WORKSPACE/PROJECT/HTML/tabler-dev`; do not hand-write a replacement
layout when a matching Tabler page/component exists.

## Adding A Module

Use the detailed checklist in `docs/module-development.md`. The short version:

Create modules with the scaffold command:

```bash
php artisan module:make core acl
php artisan module:make package page
php artisan module:make plugin blog
php artisan module:make theme default
```

Use the right layer:

- `core`: required platform foundation.
- `package`: shared CMS capability.
- `plugin`: optional project feature.
- `theme`: public frontend presentation.

After scaffolding:

```bash
composer dump-autoload
composer test
npm run build
```

The scaffold creates:

- `composer.json`
- `package.json`
- `src/Providers/*ServiceProvider.php`
- `config/*.php`
- `routes/web.php`
- `resources/views/placeholder.blade.php`
- `resources/css/admin.css`
- `resources/js/admin.js`
- `database/migrations/.gitkeep`

## Module Registration

Each module owns its provider list in its `composer.json`:

```json
{
    "extra": {
        "laravel": {
            "providers": [
                "Sitewyn\\Plugins\\Blog\\Providers\\BlogServiceProvider"
            ]
        }
    }
}
```

The core base package scans platform module files and registers providers from
that metadata. Do not add module providers manually to `bootstrap/providers.php`.

## Permissions And Menus

Until dedicated registries are implemented, document intended keys in the module
README before adding admin screens.

Permission keys should use:

```text
<module>.<action>
```

Examples:

```text
posts.index
posts.create
posts.edit
posts.destroy
```

Admin route names should use:

```text
admin.<module>.<action>
```

Examples:

```text
admin.posts.index
admin.posts.create
```

## Module Checklist

Before closing a module task:

- The module lives in the correct `platform/` layer.
- Naming follows `docs/naming.md`.
- Composer package metadata is valid.
- Provider registration is colocated in the module `composer.json`.
- Routes, views, config, migrations, and assets stay inside the module.
- Admin UI uses Tabler conventions.
- `composer test` passes.
- `npm run build` passes when assets changed.
