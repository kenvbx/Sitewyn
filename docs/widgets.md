# Widgets

Widget areas (P5-04) let a theme expose named slots — sidebar, footer, … —
that editors fill with ready-made blocks in the admin. The theme owns the
presentation, core owns the data.

## Flow

```
theme.json ("widget_areas")  →  ThemeManager::widgetAreas()
        →  Admin /admin/widgets (rows in the `widgets` table)
        →  WidgetRenderer::resolveWidgets(area)
        →  <x-widget-area slug="footer" />  →  theme partial widgets/{type}
```

1. **Declaration** — the active theme's `theme.json` lists its areas:
   `"widget_areas": [{"slug": "footer", "name": "Footer widgets"}]`. Slugs
   must match `^[a-z0-9_-]+$`; entries missing a slug/name are dropped. There
   is no `widgets`-area table: the area exists only in the manifest, so the
   admin validates `area_slug` against `ThemeManager::widgetAreas()` at
   runtime instead of a FK.
2. **Admin** — `/admin/widgets` (permission `widgets.manage`, module
   `core/base`) picks an area, then creates/edits/reorders (↑/↓ swap with the
   neighbour)/deletes widgets. Every widget row stores `area_slug`, `type`,
   `data` (JSON) and `order`.
3. **Resolution** — `WidgetRenderer::resolveWidgets(area)` returns the
   widgets in order with their payload:
   - `pages` → all published pages (title-ordered), optional `title` heading
   - `recent-posts` → up to `limit` (1–20, default 5) latest published posts
   - `text` → admin-authored rich text (`content`), rendered raw like page
     content
   Unknown types are skipped silently.
4. **Rendering** — `<x-widget-area slug="footer" />` includes the top-level
   `widgets.{type}` view for each widget; the active theme's
   `resources/views/widgets/*.blade.php` win (the view finder prepends the
   theme). A type the theme ships no partial for is skipped; an area with
   nothing to render outputs nothing, so untouched layouts keep their markup.

The default theme declares the `footer` area and renders it above the credit
line in `frontend.layout`, with matching widget styles in `theme.css`.

## Widget types

| Type            | data payload                                | Frontend output                     |
| --------------- | ------------------------------------------- | ----------------------------------- |
| `pages`         | `{title?}`                                  | List of published pages, `/{slug}`  |
| `recent-posts`  | `{title?, limit: 1-20}`                     | N latest published posts, `/blog/{slug}` + date |
| `text`          | `{title?, content}`                         | Raw admin-authored HTML             |

`title` is an optional heading for all three; the theme hides the heading
when it is empty.

## Tests

```bash
vendor/bin/phpunit --filter AdminWidgetTest
```
