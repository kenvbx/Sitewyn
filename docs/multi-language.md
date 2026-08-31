# Multi-Language Content (P5-01)

Content translation for pages, posts, and categories, introduced by P5-01. The
governing product decision: **the site ships with exactly one language
(English); anyone who wants another language adds it from the Settings area.**
No other part of the system assumes more than one language exists.

```text
platform/core/base        languages table, Language model, LanguageController,
                          Support\Translations helper
platform/packages/page    page_translations table + PageTranslation model
platform/packages/blog    post_translations/category_translations tables +
                          PostTranslation/CategoryTranslation models
```

## Architecture Decisions (final)

1. **`languages` table (core/base)** — `id`, `code` string(10) unique (two
   lowercase letters, ISO 639-1), `name`, `is_default` (bool), `is_active`
   (bool), timestamps. The migration
   (`2026_08_30_000011_create_languages_table`) **seeds English directly**
   (`code: en`, `is_default: true`, `is_active: true`) when the table is
   empty, so every install always has exactly one default language. Seeding
   in the migration — not a seeder — also keeps the translations tables
   created right after it FK-valid from the first run.
2. **Language management lives in the Settings area and reuses the existing
   `settings.edit` permission.** There is deliberately **no new permission
   key** — no ripple through `PermissionRegistry`, roles, or
   `permission:sync`. The dedicated page `/admin/settings/languages`
   (route names `admin.settings.languages.*`) supports:
   - `GET  /admin/settings/languages` — list (default language first).
   - `POST /admin/settings/languages` — add `{code, name}`; code must be two
     lowercase letters (`regex:/^[a-z]{2}$/`, `size:2`) and unique.
   - `POST /admin/settings/languages/{language}/delete` — the **default
     language can never be deleted** (flash error); deleting any other
     language cascades all of its translations (FK below).
   - `POST /admin/settings/languages/{language}/make-default` — promotes the
     language and demotes the old default in one transaction; the default is
     always active. There is intentionally no deactivate toggle in the UI:
     `is_active` exists for future use, and "the default can never be
     inactive" is enforced by `makeDefault` always setting `is_active=true`.
   - A **Manage languages** link is rendered on the settings form
     (`core/base::admin.settings.edit`), and the sidebar `Settings` item
     (active pattern `admin.settings.*`) highlights on the languages page.
3. **One translation table per owning package** — `page_translations`
   (`2026_08_30_000012`, page package), `post_translations`
   (`2026_08_30_000013`), `category_translations` (`2026_08_30_000014`, blog
   package). Common shape: `id`, `{parent}_id` FK `cascadeOnDelete`,
   `locale` string(10) **FK → `languages.code` with `cascadeOnDelete`**,
   nullable content columns, timestamps, and `unique(parent_id, locale)`.
   Nullable fields are the fallback mechanism: a translation stores only the
   fields it overrides. Page/post translations carry
   `title`/`content`/`seo_title`/`seo_description`; category translations
   carry `name` only. Models (`PageTranslation`, `PostTranslation`,
   `CategoryTranslation`) use `#[Fillable]` and the parents expose
   `translations()` `hasMany` relations. The `locale` FK is what makes
   "delete a language removes its translations" true at the database level —
   core/base never has to know which packages own translation tables.
4. **Slugs are shared from the default language (most important decision).**
   A translation **never owns a slug**. The URL of a translated page/post is
   `/{locale}/{default-slug}` — e.g. `/vi/blog/hello-world`. Rationale:
   `SlugService` and the cross-table unique slug namespace (`pages` +
   `posts`, plus `categories`/`tags` own namespaces) stay untouched, the
   admin forms stay unchanged for slugs, and lookup by slug remains
   unambiguous. **Per-locale slugs are a future enhancement** (they would
   need a slug column on each translation table plus a localized-slug
   lookup strategy; noted here so the decision is traceable).
5. **Default-language content is never localized.** `Language::translatable()`
   returns the active, **non-default** languages; `Language::findTranslatable()`
   resolves a locale code with the same filters. The default language is
   served by the existing un-localized routes and localized routes 404 for
   its code (e.g. `/en/about-us` → 404 while `en` is the default). After
   `make-default` switches the default, the old default simply becomes a
   normal translatable language.

## Admin UI And Persistence

Each page/post/category form (`form.blade.php`) renders a **Translations**
section at the bottom, inside the same `<form>`:

- One `x-admin-card` per active non-default language, titled e.g.
  `Vietnamese (VI)`, containing: `title` (or `name` for categories), a
  `x-admin-editor` content field (pages/posts only), and
  `seo_title`/`seo_description`. Inputs submit as
  `translations[vi][title]`, `translations[vi][content]`, ... Categories
  submit `translations[vi][name]`.
- Every input's **placeholder shows the default-language content** so the
  translator can see what they are translating (the editor placeholder shows
  a tag-stripped, truncated preview).
- With no extra languages configured the section shows
  *"Add languages in Settings to translate content."* with a link.
- When editing, existing translations prefill the fields; validation errors
  render inline per field (`@error('translations.'.$locale.'.title')`).

Flow on store/update (`PageController`, `PostController`,
`CategoryController`):

1. The FormRequests validate `translations` as `nullable` `array`, each
   field `nullable` `string` with sane max lengths, and — via
   `Translations::localeKeyRule()` — every **locale key must be an active,
   non-default language**. Forged or stale keys (including the default
   locale) fail with 422; the page/post/category is never created partially.
2. The controller strips `translations` from the validated attributes (they
   are not mass-assignable content columns) and persists the parent row
   through the repository exactly as before.
3. `Translations::save($parent->translations(), $input, $fields)` then
   upserts one row per locale (`updateOrCreate` on `(parent_id, locale)`).
   If **every** field of a locale is empty the row is **deleted outright** —
   no blank rows linger, and clearing a locale in the UI falls back cleanly
   to the default language. Locale keys are re-checked against
   `Language::translatable()` here too, so the helper is safe for non-HTTP
   callers.

## Frontend Localized Routes

Two new public routes (route names chosen to mirror the existing pairs):

```text
GET /{locale}/blog/{slug}   blog.posts.localized   PostPublicController@showLocalized
GET /{locale}/{slug}        pages.localized        PagePublicController@showLocalized
```

- The locale segment must match `[a-z]{2}` and must resolve to an **active,
  non-default language**, else 404. The parent must be `published` (same
  lookup as the un-localized routes) — drafts 404 in every locale.
- The translation row (if any) is passed to the existing frontend views,
  which render **per-field fallback**: `title`, `content`, `seo_title`,
  `seo_description` come from the translation when set, otherwise from the
  default-language parent. `<html lang>` switches to the locale when a
  translation exists. `og_image`, featured image, tags, and dates stay
  default-language values (a deliberate MVP simplification).

**Route-swallowing safety** (why nothing else can be captured):

- `/{locale}/{slug}` is two segments and `locale` is exactly two lowercase
  letters — reserved first segments (`admin`, `blog`, `api`, `storage`,
  `_platform`, `build`, `vendor`, `up`, `login`, ...) can never match.
- It is registered **before** the page catch-all `/{slug}` (which is one
  segment and stays the last route in the page package's route file), so the
  catch-all ordering invariant is preserved.
- `/{locale}/blog/{slug}` is three segments: it cannot collide with the
  one-segment catch-all or the two-segment localized page route, and
  `/{locale}/blog/{slug}` never matches `/blog/{slug}` because `blog` is not
  two letters. `/vi/blog` (two segments) is treated as a *page* lookup for
  the slug `blog` — there is no localized blog index in this round; it 404s
  unless someone creates a page with that slug (same caveat as reserved
  slugs on the catch-all, see `docs/pages.md`).

Verified mechanically with `artisan route:list` and
`TranslationsTest` (localized URLs, fallbacks, 404 matrix, plus the
default-language URLs continuing to work).

## MVP Limits (documented, intentional)

- **Per-locale slug**: not implemented (decision 4 above) — enhancement.
- **`hreflang` / alternate links**: not emitted on frontend pages yet.
- **Sitemap locale URLs**: `/sitemap.xml` still lists only default-locale
  URLs; no `/{locale}/...` entries in this round.
- **No locale switcher UI** on the frontend; localized URLs are
  addressable but not cross-linked yet.
- **No deactivation toggle** in the languages UI; `is_active=false` is only
  reachable programmatically (used by the 404 test paths).
- Localized routes exist only for page/post **detail** pages; there is no
  localized index/archive, and category translations affect admin data only
  (no frontend category pages exist yet).

## Tests

```bash
vendor/bin/phpunit --filter AdminLanguagesTest
vendor/bin/phpunit --filter TranslationsTest
```

`AdminLanguagesTest` covers the seeded English default, permission guards
(reusing `settings.edit`), add/make-default/delete rules, and the FK cascade.
`TranslationsTest` covers the admin translation UI (hints, cards,
placeholders), persistence (create/update-without-duplicates/clear-on-empty,
rejected locale keys), the localized frontend routes with per-field fallback,
and the 404 matrix (unknown/inactive/default locale, drafts).
