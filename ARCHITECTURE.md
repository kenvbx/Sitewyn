# Sitewyn CMS Architecture

Sitewyn is organized as a modular Laravel CMS. The Laravel application remains
the host application, while CMS capabilities live under `platform/`.

## Directory Layers

```text
platform/
  core/
  packages/
  plugins/
  themes/
```

### `platform/core`

Required foundation modules. The application should not boot the admin or CMS
without these modules.

Expected responsibilities:

- Admin shell and shared layout
- Access control
- Settings
- Media foundation
- Shared response and support helpers
- Module, menu, permission, and asset registration contracts

### `platform/packages`

Reusable CMS capabilities that are part of the product baseline, but are not as
low-level as `core`.

Expected examples:

- Pages
- Slugs
- Menus
- Widgets
- Theme support
- SEO helpers

### `platform/plugins`

Optional product features that can be enabled per project.

Expected examples:

- Blog
- Contact inbox
- Gallery
- Member area
- Backup
- Activity logs
- Multi-language support

### `platform/themes`

Frontend presentation packages. Themes own public layouts, partials, views,
frontend assets, and optional theme-specific functions.

Admin UI does not live here. Admin UI belongs to `platform/core/base`.

## Module Shape

Each module should follow this shape when the folder is relevant:

```text
module-name/
  package.json
  composer.json
  config/
  database/
    migrations/
    seeders/
  resources/
    css/
    js/
    views/
    lang/
  routes/
    web.php
    api.php
  src/
    Http/
    Models/
    Providers/
    Support/
```

Do not create empty folders unless the module needs them. Use `.gitkeep` only
for root layer placeholders.

## Naming

- PHP namespace root: `Sitewyn`
- Module namespaces should mirror their layer and module name.
- Composer package names should use `sitewyn/*`.
- Frontend package names should use `@sitewyn/*`.

Examples:

- `Sitewyn\Core\Base`
- `Sitewyn\Packages\Page`
- `Sitewyn\Plugins\Blog`
- `sitewyn/core-base`
- `@sitewyn/core-base`

## Admin UI

Tabler is the baseline admin interface library. Admin screens should preserve
Tabler layout, spacing, responsive behavior, form states, tables, tabs, modals,
dropdowns, cards, and navigation patterns.

Any custom admin styling must be small, scoped, and justified by a real product
need.

## Asset Ownership

The root Vite config discovers module entries from these paths:

- `platform/core/*/resources/css/app.css`
- `platform/core/*/resources/css/admin.css`
- `platform/core/*/resources/js/app.js`
- `platform/core/*/resources/js/admin.js`
- The same pattern under `platform/packages`, `platform/plugins`, and
  `platform/themes`

Core admin assets start at:

```text
platform/core/base/resources/css/admin.css
platform/core/base/resources/js/admin.js
```

## Feature Priority

Build common CMS workflows first:

- Posts
- Pages
- Media library
- Categories and tags
- Menus
- Themes
- Widgets
- Users and roles
- Settings

Advanced capabilities should start as plugins unless they are required by the
core admin experience.

## Placement Rules

- Put Laravel host bootstrapping in the normal Laravel folders.
- Put CMS foundations in `platform/core`.
- Put reusable CMS features in `platform/packages`.
- Put optional features in `platform/plugins`.
- Put public site presentation in `platform/themes`.
- Avoid placing CMS product logic directly in `app/` when it has a clear module
  owner.
