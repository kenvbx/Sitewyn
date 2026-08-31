# Menus

Frontend navigation menus (P5-03). Admins build menus in a drag-and-drop
builder and assign them to a named theme location; the default theme renders
the menu assigned to `primary` instead of its automatic published-pages nav.

## Schema

Two tables in `platform/core/base/database/migrations/`:

- `menus` — `name` (string 191), `slug` (string 191, **unique**), `location`
  (string 50, nullable), timestamps. A menu with a null location is a draft
  that renders nowhere.
- `menu_items` — `menu_id` (FK → menus, cascade on delete), `parent_id`
  (nullable FK → menu_items, null on delete — deleting a parent promotes its
  children to the top level), `label` (string 191), `type` (`page` | `post` |
  `custom`), `target_id` (unsigned big int, nullable), `url` (string 500,
  nullable), `order` (int, default 0), timestamps, index `(menu_id, order)`.

There is deliberately **no FK on `target_id`**: it points into the pages or
posts tables depending on `type`, and cross-package foreign keys would couple
core migrations to package tables. The builder validates the target by hand
instead (row must exist in the referenced table at save time).

## Models

- `Sitewyn\Core\Base\Models\Menu` — `items()` hasMany (save order), and
  `Menu::forLocation(string $location): ?Menu` which eager-loads items with
  their children — the exact shape the nav renderer needs.
- `Sitewyn\Core\Base\Models\MenuItem` — `menu()`, `parent()`, `children()`
  relations and a `defaultOrder` scope (`order`, then `id`).

## Admin area

All routes live under `/admin/menus`, gated by the single permission
`menus.manage` (group `menus`, module `core/base`, registered by
`BaseServiceProvider`; sidebar item "Menus", icon `menu`, order 25):

| Route | Purpose |
| --- | --- |
| `GET /admin/menus` | Menu list (name, slug, location badge, item count) |
| `GET /admin/menus/create` | Name + slug + location form |
| `POST /admin/menus` | Store (redirects into the builder) |
| `GET /admin/menus/{menu}/edit` | Edit menu settings |
| `PUT /admin/menus/{menu}` | Update settings |
| `GET /admin/menus/{menu}/edit-items` | **Builder** (two panels) |
| `POST /admin/menus/{menu}/items` | Save the whole structure |
| `DELETE /admin/menus/{menu}` | Delete (items cascade) |

### Slugs and locations

- Slug empty → generated from the name (SlugService). A duplicated slug gets
  a `-2`, `-3`, ... suffix instead of a validation error — same pattern as
  pages/posts/categories.
- An empty slug on update keeps the current one.
- One menu per location: assigning `primary` to a menu releases it from the
  previous holder (set to null), like the default-language pattern.

### Builder flow

`edit-items` renders two panels:

- **Add items** (left): checkbox lists of published pages and posts plus a
  custom URL form (label + URL). "Add" appends rows to the structure panel.
  Custom URLs accept a site path (`/contact`) or a full `http(s)` URL.
- **Menu structure** (right): one row per item — drag handle, inline-editable
  label, type badge (Page/Post/Link with the target's title or URL as
  tooltip), indent/outdent (one nesting level max), ↑/↓ buttons as an
  accessibility fallback, and remove. Reordering uses hand-rolled HTML5 drag
  and drop (~vanilla JS, no dependency, inline `@once @push('scripts')` in
  the builder view): drag **reorders within a level**, nesting stays on the
  indent/outdent buttons. Deleting a parent promotes its children, matching
  the `parent_id` FK behaviour.

**Save replaces everything.** The form posts the flat row list as
`items[{i}][id|label|type|target_id|url|parent_id|order]`; the server deletes
all items of the menu and re-creates them in one transaction, remapping each
row's `parent_id` (a request-scoped id: database ids for existing rows,
client-generated `n1`, `n2`, ... for fresh rows) onto the new rows. Nothing
outside the save references a menu item id, so new ids every save are safe.
After a failed validation the builder re-renders the rows from the old input
so the arrangement is not lost.

### Item validation (`StoreMenuItemsRequest`)

Per row: `label` required (max 191 — never auto-derived in the MVP), `type`
in `page|post|custom`, `target_id` required + must exist for page/post rows,
`url` required for custom rows, matching `^(/.*|https?://\S+)$` (max 500 —
the scheme allow-list keeps `javascript:`/`data:` payloads out of the nav).
Cross-row: ids must be unique, `parent_id` must reference **another row of
the same request** (never itself, never an id outside the payload), and
parents cannot themselves have a parent — one nesting level, enforced.

## Frontend rendering

`platform/themes/default/resources/views/frontend/layout.blade.php`:

- `Menu::forLocation('primary')` decides the nav. Menu items store page/post
  **target ids**, so slugs are resolved at render time (batched into one
  query per type, published rows only) and dead targets — deleted or
  unpublished pages/posts — silently drop out of the nav.
- Hrefs: page → `/{page-slug}`, post → `/blog/{post-slug}`, custom → the
  stored URL. External `http(s)` links get `target="_blank" rel="noopener"`.
- Children render as a nested `<ul class="site-nav-children">` right after
  their parent link (inline indented — no dropdown JS in the MVP).

### Fallback

If no menu holds the primary location **or the menu has no items**, the theme
falls back to its original behaviour: a nav of published pages. Deleting a
menu therefore restores the automatic pages nav with zero regression.

## MVP limits (by design)

- One location: `primary`. Other locations can be stored but nothing renders
  them and the builder only offers `primary`.
- No dropdown/menu JS on the frontend; children are inline-indented.
- No per-locale menus (translations of the site do not switch menus).
- Custom links are not re-validated against liveness; page/post targets are
  skipped (not hidden) when they stop being published.
- Per-item labels are mandatory; the builder pre-fills them with the page or
  post title but never silently derives them on the server.

## Tests

```bash
vendor/bin/phpunit --filter AdminMenuTest
vendor/bin/phpunit --filter PermissionRegistryTest
vendor/bin/phpunit --filter AdminMenuRegistryTest
```

`AdminMenuTest` covers the schema (columns, unique slug, cascade), menu CRUD,
slug/location handling, permission gating on all 8 routes, replace-all item
saves (nesting, order, and every rejection case), and the frontend nav
(replacement, nesting, ordering, external links, fallback restore).
