# Blog

P3-07 completes the admin Post CRUD inside the blog package at:

```text
platform/packages/blog
```

The package is registered through its own `composer.json` provider metadata and
is discovered by the core base module provider scanner. Models, repositories,
and the schema migrations (`categories`, `posts`, `tags`, `post_tag`) landed in
P3-04; P3-07 adds the admin post layer and P3-08 adds standalone Category and
Tag management screens on top.

> **Plugin Manager (P4-08):** the blog package now ships a `plugin.json`
> manifest (slug `blog`) and is a standard, manageable plugin — see
> `docs/plugin-development.md`. Deactivation via `/admin/plugins` or
> `plugin:deactivate blog` removes its routes/menu from the running app while
> keeping every row in `posts`/`categories`/`tags`; re-activation restores the
> module with its data intact.

## Schema

The `posts` table stores blog posts. On top of the P3-04 columns, P3-07 adds:

- `featured_image`: nullable string(255) added by the
  `2026_08_30_000006_add_featured_image_to_posts_table` migration.
- `og_image`: nullable string(255) Open Graph image URL added by P3-09
  (`2026_08_30_000008_add_og_image_to_posts_table` migration).

It stores the URL returned by the media picker. It is intentionally a plain
string, **not** a foreign key to `media_files`: the blog stays decoupled from
the media module, deleting a media file never breaks a post, and moving media
to S3/CDN (P5-09) becomes a pure data rewrite.

P5-01 adds two translation tables (same package, see `docs/multi-language.md`):

- `post_translations` (`2026_08_30_000013`): `id`, `post_id` FK
  `cascadeOnDelete`, `locale` string(10) FK → `languages.code`
  `cascadeOnDelete`, nullable `title`/`content`/`seo_title`/`seo_description`,
  timestamps, `unique(post_id, locale)`.
- `category_translations` (`2026_08_30_000014`): same shape with
  `category_id` and a nullable `name` only.

## Model And Repository

- `Sitewyn\Packages\Blog\Models\Post` with `STATUS_DRAFT` / `STATUS_PUBLISHED`,
  `category()` (BelongsTo), `tags()` (BelongsToMany), and `translations()`
  (`hasMany` to `PostTranslation`, P5-01).
- `Sitewyn\Packages\Blog\Repositories\PostRepository` supporting `all()`,
  `byStatus()`, `inCategory()`, `search(term, ?status, ?categoryId)`, `find()`,
  `findBySlug()`, `findPublishedBySlug()`, `create()`, `update()`, `delete()`.
- `Sitewyn\Packages\Blog\Repositories\TagRepository` additionally exposes
  `findByName()` for the tag sync flow.

Slug behavior (shared `Sitewyn\Core\Base\Support\SlugService`):

- Creating without a slug generates one from the title.
- Post slugs share one namespace with `pages` (`-2`, `-3`, ... suffixes), so
  public routes `/{slug}` and `/blog/{slug}` never collide.
- Category and tag slugs each live in their own namespace: uniqueness is
  checked against the `categories` / `tags` table only, never pages/posts or
  each other.
- Updating with an empty slug keeps the current slug (the controller strips the
  empty key before calling the repository).

## Admin Routes

All admin routes live under the `web`, `auth:admin`, and per-route permission
middleware:

```text
GET    /admin/posts                 post.index    admin.posts.index
GET    /admin/posts/create          post.create   admin.posts.create
POST   /admin/posts                 post.create   admin.posts.store
GET    /admin/posts/{post}/preview  post.index    admin.posts.preview
GET    /admin/posts/{post}/edit     post.edit     admin.posts.edit
PUT    /admin/posts/{post}          post.edit     admin.posts.update
DELETE /admin/posts/{post}          post.delete   admin.posts.destroy
```

The list page supports `?q=<title search>` combined with
`?status=draft|published` and `?category_id=<id>`.

P3-08 adds the same six-route shape (no preview) for the two taxonomy screens:

```text
GET    /admin/categories                 category.index   admin.categories.index
GET    /admin/categories/create          category.create  admin.categories.create
POST   /admin/categories                 category.create  admin.categories.store
GET    /admin/categories/{category}/edit category.edit    admin.categories.edit
PUT    /admin/categories/{category}      category.edit    admin.categories.update
DELETE /admin/categories/{category}      category.delete  admin.categories.destroy

GET    /admin/tags                 tag.index   admin.tags.index
GET    /admin/tags/create          tag.create  admin.tags.create
POST   /admin/tags                 tag.create  admin.tags.store
GET    /admin/tags/{tag}/edit      tag.edit    admin.tags.edit
PUT    /admin/tags/{tag}           tag.edit    admin.tags.update
DELETE /admin/tags/{tag}           tag.delete  admin.tags.destroy
```

Both lists support `?q=<name search>`. The category list shows the parent name
(`—` for roots) and a post count; the tag list shows the slug and a post count
(both via `withCount('posts')`).

## Permissions

Registered through `PermissionRegistry` by `BlogServiceProvider` (module
`package/blog`, groups `post`, `category`, `tag`):

- `post.index`: view the post list and previews.
- `post.create`: create posts.
- `post.edit`: edit posts.
- `post.delete`: delete posts.
- `category.index`: view the admin category list.
- `category.create`: create categories.
- `category.edit`: edit categories.
- `category.delete`: delete categories.
- `tag.index`: view the admin tag list.
- `tag.create`: create tags.
- `tag.edit`: edit tags.
- `tag.delete`: delete tags.

**Naming convention**: keys use the **singular** resource name (`post.index`,
not `posts.index` as the original P3 spec text read). P3-07/P3-08 shipped the
singular form, matching the page package (`page.*`) so registry keys, groups,
menu guards, and `permission:sync` output stay uniform across modules;
renaming now would churn role assignments in the `permissions` table for no
benefit. The tables above are the authoritative permission → route mappings,
and `AdminPermissionCoverageTest` enforces them mechanically: every route
carrying `permission:` middleware must reference a key registered in the
`PermissionRegistry`, or the whole suite fails (a route tied to an
unregistered key would 403 for every admin, forever).

Sidebar items `Posts` (icon `post`, order 21), `Categories` (icon `category`,
order 22) and `Tags` (icon `tag`, order 23) sit right after Pages and each
requires its own `*.index` permission. Action buttons and delete modals in the
lists render only for admins holding the matching permission.

Run `php artisan permission:sync` after deploying so the new permissions land
in the database.

## Category And Tags

- **Category** is a single select on the post form (`— None —` clears it; the
  column is nullable and `nullOnDelete` already handles removed categories).
- **Tags** use a plain text input (`tags_input`, not part of `Post::$fillable`)
  with comma-separated names, e.g. `Laravel, PHP`. Server-side sync in
  `PostController::syncTags()`:

  1. split on commas, trim, drop empties, dedupe case-insensitively;
  2. for each name, reuse the existing tag found via
     `TagRepository::findByName()`;
  3. create missing tags with a `SlugService` slug unique within `tags`;
  4. `sync()` the resulting ids onto the post.

  Updates always re-sync from scratch, so removing a name detaches it from the
  post (the tag itself stays in the library for other posts). Omitting
  `tags_input` on an update clears the post's tags.

## Category Management

The standalone Categories screen manages the hierarchy tree. A category stores
`name`, `slug` (unique within `categories`), an optional `description`, and an
optional `parent_id` (self-referencing FK with `nullOnDelete`).

Tree rules (cycle prevention) — a category may never become its own ancestor:

- **UI**: the edit form's Parent select is built server-side by
  `CategoryController::parentOptions()`, which excludes the category itself and
  its whole subtree (descendants collected level by level from the `children`
  relation via `Category::descendants()`). The create form offers every
  category as a parent.
- **Server**: `UpdateCategoryRequest` mirrors the UI with a validation closure —
  `parent_id` must not equal the category's own id and must not be one of its
  descendants; `exists:categories,id` (both store and update) rejects unknown
  parents. The UI hides the choices, the closure blocks crafted requests.

Delete behavior (both driven by existing `nullOnDelete` FKs, verified in
`AdminCategoryCrudTest`):

- Deleting a parent moves its children to the root (`parent_id` → null).
- Deleting a category that has posts makes those posts uncategorized
  (`posts.category_id` → null).

## Tag Management

The standalone Tags screen manages a flat list (`name` + `slug`, no hierarchy).
Tag slugs live in their own namespace — a tag named like an existing page or
post slug still gets the plain slug; only duplicates inside `tags` are suffixed
(`-2`, `-3`, ...).

- Tag CRUD does not attach posts; attaching still happens from the post form's
  comma-separated `tags_input` (or the pivot directly).
- Deleting a tag cascades its `post_tag` pivot rows only: posts lose that tag
  but keep their other tags, and no post is ever deleted.

## Translations (P5-01)

The post form renders a **Translations** section below the main cards (inside
the same `<form>`): one card per active non-default language with `title`, a
content editor, and `seo_title`/`seo_description`, submitting as
`translations[vi][title]` etc. The category form gets the same section with a
`name` field only. Placeholders show the default-language content; when no
extra languages exist the section hints *"Add languages in Settings to
translate content."* — languages are managed at `/admin/settings/languages`
(reusing `settings.edit`). Locale keys outside the active non-default set are
rejected with 422; a locale whose fields are all empty gets its translation
row deleted. Details, tests, and the frontend behavior: `docs/multi-language.md`.

## Editor, Media Picker, And Featured Image

Create/edit forms use the shared `<x-admin-editor name="content">` component
(TinyMCE). The page also renders `<x-media-picker>` for admins with
`media.index`, which provides the `admin:editor-file-picker` listener described
in the module development docs: the editor's Image button opens the media
picker, and choosing a file inserts its URL into the content.

The featured image card reuses the same bridge: its **Choose image** button
dispatches `admin:editor-file-picker` with a callback that writes the picked
URL into the hidden `featured_image` input and renders a thumbnail preview; a
**Clear** button resets it. Unlike the page form, the hidden input *is* a post
field and submits with the main form.

Two implementation notes:

- The picker component ships its own search `<form>`, so it is rendered
  **outside** the post `<form>` (nesting forms would break the picker script).
  Its hidden inputs (`post_media`, `post_media_url`) are not post fields and
  never submit.
- The picker's Choose/Clear flow works purely client-side; validation stores
  whatever URL string is submitted (`nullable`, `max:255`), so no media lookup
  happens in the blog package.

## SEO Fields

P3-09 gives the create/edit forms a dedicated **SEO** card, shared with the
page form via the partial `core/base::admin.partials.seo-fields`:

- `seo_title` and `seo_description` show a live character counter ("N/60" and
  "N/160", Google's display limits). Passing the limit only turns the counter
  red — submit is never blocked; the DB limits (255/500) still apply through
  the input `maxlength` and the request rules.
- `og_image` is a plain URL text input, nullable, max 255. It cannot open the
  media picker (the `admin:editor-file-picker` bridge only opens the first
  picker instance on the page) — paste an URL manually for now.
- Post-only convenience: a small **Use featured image** button under the
  og:image field copies the current `#featured_image` input value into
  `og_image` client-side (plain JS, one-shot copy — pick the featured image
  first, then click it).

## Preview

`GET /admin/posts/{post}/preview` renders a minimal standalone view with the
post title, a status/slug/category/tags meta line, the featured image if set,
and the stored content. Draft posts show a `PREVIEW — DRAFT` notice bar and a
diagonal watermark; published posts render without the draft markings. The
view is marked `noindex, nofollow`. Content is admin-authored rich text and is
rendered as stored HTML.

## Public Frontend

P3-10 serves published posts on the public site (no theme yet — same minimal
standalone style as the admin preview):

```text
GET /blog/{slug}   blog.posts.show   PostPublicController@show
```

- Only `published` posts resolve, via `PostRepository::findPublishedBySlug()`.
  Drafts and unknown slugs return the framework's plain 404 — drafts are never
  exposed publicly (the admin preview stays the only way to see them).

P5-01 adds the localized counterpart **after** the detail route:

```text
GET /{locale}/blog/{slug}   blog.posts.localized   PostPublicController@showLocalized
```

The slug stays the default language's (translations never own slugs); the
translation only overrides `title`/`content`/`seo_title`/`seo_description`
with per-field fallback, and `<html lang>` switches to the locale. Unknown,
inactive, or default locales 404 — see `docs/multi-language.md` for the full
route-safety analysis.

**Home page**: the CMS front page `GET /` (route name `home`, app-layer
`App\Http\Controllers\HomeController`) is WordPress-style — it lists the
latest published posts, newest first, each with a plain-text excerpt (stored
HTML stripped, whitespace collapsed, capped at ~160 characters) linking to
`/blog/{slug}`, plus a compact index of published pages linking to `/{slug}`.
It renders `frontend.home`, which resolves into the active theme (see
`docs/themes.md`); the default theme shows an English-only empty state when
no published content exists. Drafts never appear.
- **Route naming**: the route name is `blog.posts.show`, mirroring the `/blog/`
  URL prefix; the page catch-all counterpart is `pages.show` (page package).
- **Slug namespace**: post slugs share one namespace with `pages` (P3-04), so
  `/blog/{slug}` only ever looks up posts and the page catch-all `/{slug}` only
  pages. A page slug under `/blog/{slug}` and a post slug under `/{slug}` both
  404 — the two public routes can never serve the wrong content type.
- **What renders** (`frontend.post`, resolved into the active theme — P5-02,
  see `docs/themes.md`): site header + nav, `<h1>` title, featured image when
  set, publish date (`updated_at`) plus tags as plain accent-colored text
  separated by commas (no public tag archive exists yet, so they are not
  links), and the stored content as HTML (same trust model as the admin
  preview).
- **Meta tags**: `<title>` = `seo_title ?: title`; `meta description` and
  `og:description` = `seo_description` when set; `og:title` always; `og:image`
  when `og_image` is set; `og:type` is `article`.
- **Sitemap (P5-08)**: `BlogServiceProvider::boot()` registers a contributor on
  the core `SitemapRegistry`, mapping each published post to
  `['loc' => url("/blog/{slug}"), 'lastmod' => updated_at]`; the core
  `/sitemap.xml` route invokes it per request, so new posts show up on the next
  request with no cache to clear. See `docs/admin-auth.md` (Settings section).

## Tests

```bash
vendor/bin/phpunit --filter AdminPostCrudTest
vendor/bin/phpunit --filter AdminCategoryCrudTest
vendor/bin/phpunit --filter AdminTagCrudTest
vendor/bin/phpunit --filter BlogRepositoryTest
vendor/bin/phpunit --filter BlogSchemaTest
vendor/bin/phpunit --filter PostFrontendTest
vendor/bin/phpunit --filter AdminLanguagesTest
vendor/bin/phpunit --filter TranslationsTest
vendor/bin/phpunit --filter SeoFilesTest
vendor/bin/phpunit --filter AdminPermissionCoverageTest
```
