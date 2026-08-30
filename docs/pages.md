# Pages

P3-06 completes the admin Page CRUD inside the page package at:

```text
platform/packages/page
```

The package is registered through its own `composer.json` provider metadata and
is discovered by the core base module provider scanner.

## Schema

The `pages` table (P3-04 migration) stores static pages:

- `id`
- `title`
- `slug`: unique, shares one namespace with `posts` so public routes never collide.
- `content`: nullable long text (rich HTML from the shared admin editor).
- `seo_title`, `seo_description`: nullable SEO metadata.
- `og_image`: nullable string(255) Open Graph image URL added by P3-09
  (`2026_08_30_000007_add_og_image_to_pages_table`).
- `status`: `draft` (default) or `published`, indexed.
- `created_at`, `updated_at`

## Model And Repository

- `Sitewyn\Packages\Page\Models\Page` with `STATUS_DRAFT` / `STATUS_PUBLISHED` constants.
- `Sitewyn\Packages\Page\Repositories\PageRepository` supporting `all()`,
  `byStatus()`, `search(term, ?status)`, `find()`, `findBySlug()`,
  `findPublishedBySlug()`, `create()`, `update()`, `delete()`.

Slug behavior (shared `Sitewyn\Core\Base\Support\SlugService`):

- Creating without a slug generates one from the title.
- Duplicate slugs are suffixed `-2`, `-3`, ... across `pages` **and** `posts`.
- Updating with an empty slug keeps the current slug (the controller strips the
  empty key before calling the repository). Providing a manual slug keeps it,
  suffixing only when another page or post already owns it.

## Admin Routes

All admin routes live under the `web`, `auth:admin`, and per-route permission
middleware:

```text
GET    /admin/pages                 page.index    admin.pages.index
GET    /admin/pages/create          page.create   admin.pages.create
POST   /admin/pages                 page.create   admin.pages.store
GET    /admin/pages/{page}/preview  page.index    admin.pages.preview
GET    /admin/pages/{page}/edit     page.edit     admin.pages.edit
PUT    /admin/pages/{page}          page.edit     admin.pages.update
DELETE /admin/pages/{page}          page.delete   admin.pages.destroy
```

The list page supports `?q=<title search>` combined with `?status=draft|published`.

## Permissions

Registered through `PermissionRegistry` by `PageServiceProvider` (module
`package/page`, group `page`):

- `page.index`: view the page list and previews.
- `page.create`: create pages.
- `page.edit`: edit pages.
- `page.delete`: delete pages.

**Naming convention**: keys use the **singular** resource name (`page.index`,
not `pages.index` as the original P3 spec text read). P3-06 shipped the
singular form and every module has kept it since (`post.*`, `category.*`,
`tag.*` in the blog package), so registry keys, groups, menu guards, and
`permission:sync` output stay uniform; renaming now would churn role
assignments in the `permissions` table for no benefit. The table above is the
authoritative permission → route mapping, and
`AdminPermissionCoverageTest` enforces it mechanically: every route carrying
`permission:` middleware must reference a key registered in the
`PermissionRegistry`, or the whole suite fails (a route tied to an
unregistered key would 403 for every admin, forever).

The sidebar item `Pages` (icon `page`, order 20, before Media) requires
`page.index`. Action buttons and delete modals in the list render only for
admins holding the matching permission.

Run `php artisan permission:sync` after deploying so the new permissions land
in the database.

## Editor And Media Picker Bridge

Create/edit forms use the shared `<x-admin-editor name="content">` component
(TinyMCE). The page also renders `<x-media-picker>` for admins with
`media.index`, which provides the `admin:editor-file-picker` listener described
in the module development docs: the editor's Image button opens the media
picker, and choosing a file inserts its URL into the content.

Two implementation notes:

- The picker component ships its own search `<form>`, so it is rendered
  **outside** the page `<form>` (nesting forms would break the picker script).
- The picker's hidden inputs are not page fields; nothing from the picker is
  stored on the page — it only powers the editor bridge.

## SEO Fields

P3-09 gives the create/edit forms a dedicated **SEO** card, shared with the
post form via the partial `core/base::admin.partials.seo-fields`:

- `seo_title` and `seo_description` show a live character counter ("N/60" and
  "N/160", Google's display limits). Passing the limit only turns the counter
  red — submit is never blocked; the DB limits (255/500) still apply through
  the input `maxlength` and the request rules.
- `og_image` is a plain URL text input, nullable, max 255. Like `featured_image`
  on posts it is deliberately a plain string, not a `media_files` foreign key.

Known limitation: `og_image` cannot open the media picker. The
`admin:editor-file-picker` bridge opens the first picker instance on the page
(the editor's own), and a second picker instance would need a
multi-instance-aware bridge — paste an URL manually for now.

## Preview

`GET /admin/pages/{page}/preview` renders a minimal standalone view with the
page title, a status/slug meta line, and the stored content. Draft pages show a
`PREVIEW — DRAFT` notice bar and a diagonal watermark; published pages render
without the draft markings. The view is marked `noindex, nofollow`. Content is
admin-authored rich text and is rendered as stored HTML.

## Public Frontend

P3-10 serves published pages on the public site (no theme yet — same minimal
standalone style as the admin preview):

```text
GET /{slug}   pages.show   PagePublicController@show
```

- Only `published` pages resolve, via `PageRepository::findPublishedBySlug()`.
  Drafts and unknown slugs return the framework's plain 404 — drafts are never
  exposed publicly (the admin preview stays the only way to see them).
- **Route-swallowing protection**: `/{slug}` is a single-segment catch-all, so it
  is the last route in the page package's `routes/web.php` and carries a
  `where()` regex excluding the reserved first segments `admin`, `blog`, `api`,
  `_platform`, `storage`, `build`, `vendor`, `up`, `login`, `logout`,
  `register`, `password`, `reset`. Without it, single-segment URLs like `/blog`
  or `/up` would be swallowed by the page lookup instead of falling through to
  the framework (e.g. `/admin` still redirects to login, `/up` stays the health
  check). Only exact matches are excluded: `blog-slug` or `administrator` still
  reach the page lookup.
- **Slug namespace**: P3-04 keeps slugs unique across `pages` **and** `posts`,
  so `/{slug}` only ever looks up pages and `/blog/{slug}` (blog package) only
  posts — the lookup can never be ambiguous. A post slug under `/{slug}` and a
  page slug under `/blog/{slug}` both 404.
- **Meta tags**: `<title>` = `seo_title ?: title`; `meta description` and
  `og:description` = `seo_description` when set; `og:title` always; `og:image`
  when `og_image` is set; `og:type` is `website`. The view
  (`package/page::frontend.show`) is standalone HTML with a site-name header and
  the stored content rendered as HTML (same trust model as the admin preview:
  authored by admins holding `page.create`/`page.edit`).

## Tests

```bash
vendor/bin/phpunit --filter AdminPageCrudTest
vendor/bin/phpunit --filter PageRepositoryTest
vendor/bin/phpunit --filter PageFrontendTest
vendor/bin/phpunit --filter AdminPermissionCoverageTest
```
