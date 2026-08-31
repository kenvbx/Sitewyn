# Themes

The public frontend is owned by **themes** living in `platform/themes/*`
(P5-02). Switching themes requires no core code changes: pick a theme in
Settings and every public page/post renders from that theme's Blade views.

## Discovery

`ThemeManager` (`platform/core/base/src/Support/ThemeManager.php`, registered
as a singleton) scans `platform/themes/*/theme.json`. Every theme needs a
manifest:

```json
{
    "name": "Sitewyn Default",
    "slug": "default",
    "version": "1.0.0",
    "description": "…",
    "author": "Sitewyn"
}
```

`name`, `slug`, and `version` are required (non-empty strings); a theme with a
missing or invalid manifest is skipped silently. `description` and `author`
are optional. `slug` must be unique — the first theme scanned under a slug
wins, entries are sorted by slug.

## Switching

- The selected theme is stored as the `active_theme` setting (default:
  `default`), editable in **Admin → Settings** (reuses `settings.edit`).
- The request validates the value against the discoverable slugs; saving a
  removed theme's slug fails validation.
- `ThemeManager::activeTheme()` resolves the setting to a manifest. If the
  setting points at a theme that no longer exists, it falls back to the
  `default` theme — the frontend never crashes because of a stale setting.
- If even `default` is undiscoverable, `activeTheme()` returns an empty
  manifest and the provider prepends nothing (frontend views simply 500
  instead of crashing during boot).

## View resolution

`BaseServiceProvider::boot()` prepends the active theme's
`resources/views` directory to the view finder, so **theme views win over
everything else**. Controllers render the top-level names:

- `frontend.layout` — shared document layout (header, nav, footer)
- `frontend.home` — CMS front page: latest published posts + a compact page
  index (extends `frontend.layout`)
- `frontend.page` — static page detail (extends `frontend.layout`)
- `frontend.post` — blog post detail (extends `frontend.layout`)

`home` receives `$posts` (published, newest first, each carrying a computed
plain-text `excerpt` attribute — stored HTML stripped and capped at ~160
characters) and `$pages` (published, title-ordered). It is the WordPress-style
front page served at `/` by the app-layer `HomeController` — the Laravel
welcome view no longer occupies the route.

The `page` and `blog` packages ship no frontend views anymore; they only pass
`$page`/`$post` + `$translation` (P5-01 fallback logic now lives in the theme
views). Admin preview views (`package/page::preview`,
`package/blog::preview`) are not themed — they stay in the packages.

A theme that only ships part of the view set (e.g. no `frontend.layout`) will
fail to resolve the missing names — ship a complete set or start from a copy
of the default theme.

## Theme assets

Theme CSS is built by the root Vite config: the entry
`platform/themes/<slug>/resources/css/theme.css` is picked up by
`npm run build` and ends up in `public/build/manifest.json`. The default
theme loads it via `@vite(['platform/themes/default/resources/css/theme.css'])`
in its layout. See `docs/assets.md` for the supported entry names.

## Default theme design

`platform/themes/default` is an editorial-minimal, content-first theme in the
spirit of the classic WordPress default theme:

- system serif headings (Georgia stack) + system sans body (17px/1.75);
- neutral palette with a restrained classic-WP blue accent
  (`#2271b1` light / `#85b7de` dark), light + dark via `prefers-color-scheme`;
- single 68ch column, "Proudly powered by Sitewyn" footer;
- front page: "Latest posts" list (title → `/blog/{slug}`, date, capped
  plain-text excerpt) with a small "Pages" index below, and a plain empty
  state ("No content yet. Sign in to the admin to create your first page or
  post.") when nothing is published — the copy is English-only for the MVP;
- near-static motion: the only transition is the link color (0.15s).

The default theme couples to the page module for its nav fallback
(published pages) and, since P5-03, to the blog module for menu items —
acceptable because it ships with the CMS; custom themes may render anything.

## Header nav source (P5-03)

The header nav resolves in this order:

1. **Menu assigned to the `primary` location** (built in Admin → Menus, see
   `docs/menus.md`). Items render in saved order — page → `/{slug}`,
   post → `/blog/{slug}`, custom → its URL (`http(s)` links open in a new
   tab); children render inline-nested one level deep. Page/post targets are
   resolved at render time and unpublished or deleted targets drop out.
2. **Fallback: published pages** — the original behaviour, used when no menu
   holds `primary` or the menu has no items. A site never loses its nav
   because menus are empty.

## Widget areas (P5-04)

Themes declare named widget areas through the manifest, right next to the
rest of the theme metadata:

```json
{
    "name": "Sitewyn Default",
    "slug": "default",
    "version": "1.0.0",
    "widget_areas": [
        { "slug": "footer", "name": "Footer widgets" }
    ]
}
```

- `ThemeManager::widgetAreas()` returns the **active theme's** declarations
  (default: none). Each entry needs a slug (matching `^[a-z0-9_-]+$`) and a
  name; malformed or duplicate entries are dropped silently.
- The admin (**Admin → Widgets**, `widgets.manage`) lists one area per
  declaration and lets editors attach built-in widgets (pages, recent-posts,
  text) to it, reorder them with ↑/↓ and delete them.
- A theme renders an area with `<x-widget-area slug="footer" />`. Widgets
  are presented by the theme's own `resources/views/widgets/{type}.blade.php`
  partials; an area without widgets renders nothing at all, so the layout
  keeps its original markup.
- See `docs/widgets.md` for the full data flow.

## Creating a new theme

1. `mkdir platform/themes/my-theme` and add the `theme.json` manifest above.
2. Create `resources/views/frontend/{home,layout,page,post}.blade.php` (copy
   the default theme as a starting point). `page`/`post` receive
   `$page`/`$post` and `$translation`; `home` receives `$posts` (with
   `excerpt`) and `$pages`.
3. Optional: `resources/css/theme.css` as your Vite entry.
4. Optional: declare `widget_areas` in the manifest and ship matching
   `resources/views/widgets/*.blade.php` partials for the widget types you
   want to present.
5. Switch to it in **Admin → Settings → Theme** — no core code changes.

## Test fixture theme

`platform/themes/test-marker` is a minimal fixture used by the automated
tests (`ThemeManagerTest`, `ThemeSwitchTest`) to prove theme switching. It is
not meant for production use; it will appear in the Settings dropdown.

## Tests

```bash
vendor/bin/phpunit --filter ThemeManagerTest
vendor/bin/phpunit --filter ThemeSwitchTest
vendor/bin/phpunit --filter PageFrontendTest
vendor/bin/phpunit --filter PostFrontendTest
```
