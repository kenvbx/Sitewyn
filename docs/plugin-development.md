# Plugin Development

How the CMS discovers plugins, stores their state, boots them, manages them
from the admin UI, and enforces `requires` dependencies. This documents
P4-01/P4-02 (discovery + state), P4-03/P4-04/P4-05 (conditional registration,
lifecycle commands, scoped migrations), P4-06/P4-07 (admin UI + shared
activation service with full dependency rules), and P4-08/P4-09 (Page/Blog as
first-class plugins + version constraints). P4 is complete: 9/9 tasks.

## Current State (P4-01 – P4-09)

- `plugins` table migration and the `Sitewyn\Core\Base\Models\Plugin` model
  (both in `platform/core/base`).
- `Sitewyn\Core\Base\Support\PluginManager` — filesystem scan plus active
  state lookup.
- `Sitewyn\Core\Base\Support\ModuleProviderRepository` — discovers and loads
  module service providers, now manifest-aware (see below).
- `Sitewyn\Core\Base\Support\PluginActivator` — the shared lifecycle engine
  behind both the console commands and the admin UI (see below).
- `plugin:list` / `plugin:activate` / `plugin:deactivate` console commands.
- `/admin/plugins` management UI guarded by the `plugins.manage` permission.
- Per-plugin scoped migrations, run on first activation.
- Full dependency rules: transitive activation checks, transitive dependent
  protection on deactivation, circular-chain detection, and light version
  constraints on `requires` (see "Version Constraints (P4-09)").
- The core content packages Page and Blog are plugins themselves (P4-08,
  see "Core Modules as Plugins (P4-08)").
- Two sample fixtures: `platform/plugins/demo-plugin` and
  `platform/plugins/demo-dependant` — they double as canonical examples and
  as test fixtures (see "Sample Plugins").

## plugin.json Manifest

A directory counts as a plugin only when it contains a `plugin.json` at its
root. Modules without a manifest (today: everything in `platform/core/*` and
`platform/packages/media`) are never managed as plugins — they always load
as before. Page and Blog ship manifests and are plugins (see "Core Modules
as Plugins (P4-08)").

Schema:

```json
{
    "name": "SEO Tools",
    "slug": "seo-tools",
    "version": "1.0.0",
    "description": "Optional human-readable description.",
    "provider": "Sitewyn\\Plugins\\SeoTools\\Providers\\SeoToolsServiceProvider",
    "autoload": {
        "psr-4": {
            "Sitewyn\\Plugins\\SeoTools\\": "src/"
        }
    },
    "requires": [],
    "migrations": "database/migrations"
}
```

- `name`, `slug`, `version` are required non-empty strings. A manifest that
  misses them, is unreadable, or is invalid JSON is skipped by the scan — the
  scan never crashes the request.
- `description` and `provider` are optional strings. A plugin without a
  `provider` can still be activated/deactivated and own migrations; it just
  registers nothing.
- `autoload` (optional) mirrors a composer `psr-4` map. The `provider` class
  is loaded through it when the plugin is not on the composer autoloader.
  When absent, a sibling `composer.json`'s `autoload.psr-4` is used instead.
- `requires` (optional) lists dependency slugs and supports two shapes: the
  plain slug list `["page"]` (unconstrained, the P4-07 form) and the map form
  `{"page": "^1.0"}` (slug → version constraint, P4-09 — see "Version
  Constraints"). Both normalize to the same slug list internally; the
  constraints travel in a separate `constraints` entry of the scan result.
  Activation verifies every requirement is available *and* active (never
  auto-activated — the error names what to activate first); deactivation is
  blocked by the transitive closure of active dependents; circular chains
  are detected up-front. See "Dependencies (P4-07)" below.
- `migrations` (optional) is the migrations directory relative to the plugin,
  defaulting to `database/migrations`. When that directory does not exist the
  plugin simply has no migrations.

## Scan Sources

`PluginManager` scans two roots, in this order:

1. `platform/plugins/*` — real plugins (source: `plugin`).
2. `platform/packages/*` — modules that want to become manageable plugins
   (source: `package`).

A slug present in both roots resolves in favor of `platform/plugins`. The
scan result is sorted by slug and cached per `PluginManager` instance (one
request / one test).

## Active State: No Row Means Active

The `plugins` table stores state only — it never defines which directories
are plugins:

| Table row | Meaning |
| --- | --- |
| none | **active** (backward-compatible default) |
| `is_active = true` | explicitly activated (sets `activated_at`) |
| `is_active = false` | explicitly deactivated |

Only deactivation writes a row with `is_active = false`. This keeps every
currently installed module active without touching the database — required
for backward compatibility with the pre-plugin era. If the table does not
exist yet (e.g. during the very first `php artisan migrate` run), the manager
treats everything as active instead of failing.

API of `Sitewyn\Core\Base\Support\PluginManager`:

- `all(): Collection<int, array>` — per plugin: `name`, `slug`, `version`,
  `description`, `provider`, `requires` (slugs), `constraints`
  (slug → version constraint, only constrained slugs), `migrations`, `path`,
  `isActive`, `source` (`plugin` or `package`).
- `find(string $slug): ?array`
- `isActive(string $slug): bool` — discoverable and not deactivated.
- `activeSlugs(): array` — available plugins minus deactivated ones; the
  safe-to-boot list used by P4-03.
- `availableSlugs(): array` — every valid manifest found on disk.
- `refresh(): void` — drops the per-instance caches after state mutations
  (used by the console commands).

## Conditional Provider Registration (P4-03)

`BaseServiceProvider::registerModuleProviders()` iterates
`ModuleProviderRepository::providerEntries()` and registers each provider
unless its plugin is deactivated:

- **Manifest wins.** A directory with a valid `plugin.json` is a plugin: the
  manifest's `provider` field *replaces* the composer.json
  `extra.laravel.providers` list entirely (the composer list is ignored), and
  registration is gated on `PluginManager::isActive(slug)`. Page and Blog
  have manifests since P4-08 — both point at the same provider class their
  composer.json already declared, so the two sources cannot disagree; the
  composer `extra.laravel.providers` field remains as a fallback that only
  applies if the manifest ever becomes invalid (then the module registers
  ungated, exactly as before the plugin era).
- **No manifest → old behaviour.** Directories without a manifest keep the
  composer-driven discovery and always register. Today that is
  `platform/core/*` and `platform/packages/media`.
- **Invalid manifest → composer fallback.** A manifest missing `name`, `slug`
  or `version` is not a plugin: the module is treated as a plain
  composer module (no slug, never gated).

Implementation detail: the active-state lookup reads the `plugins` table via
`DB::table()` (not Eloquent), because provider registration runs before any
service provider `boot()`, and the Eloquent connection resolver is only wired
up during `Illuminate\Database\DatabaseServiceProvider::boot()`. The
database manager itself is bound during its `register()`, which always runs
before `BaseServiceProvider::register()`, so the lookup is safe.

### Loading Provider Classes From plugin.json

Fixtures (and any plugin not on the composer autoloader) are loaded with the
same mechanism composer modules use: `ModuleProviderRepository` resolves the
provider class through the `autoload.psr-4` map of the plugin's own
`plugin.json` (falling back to a sibling `composer.json`), then `require_once`
the mapped file. No composer dump is needed for a new plugin.

## Lifecycle Commands (P4-04)

All three live in `platform/core/base/src/Console/Commands/` and are
registered by `BaseServiceProvider::registerCommands()`. Activate and
deactivate are thin shells over `PluginActivator` — they translate its
exceptions into console errors, nothing more.

### `php artisan plugin:list`

Table of discovered plugins: slug, name, version, source, active (✓/✗).

### `php artisan plugin:activate {slug}`

1. Unknown slug → error, exit 1.
2. Dependency gate (P4-07): the plugin must not sit on a circular
   `requires` chain, and every required slug must be available and active.
   Missing/inactive requirements → error naming them, exit 1.
3. First activation (no `plugins` row for the slug yet) runs the plugin's
   scoped migrations first; a failed migration run leaves the plugin
   deactivated (exit 1). Re-activating a plugin whose row already exists
   never re-runs migrations — the `migrations` table is the source of truth
   for what already ran.
4. Creates/updates the row with `is_active = true` and `activated_at = now()`.

### `php artisan plugin:deactivate {slug} [--rollback]`

1. Blocks deactivation while the transitive set of *active* dependents is
   non-empty (any active plugin that reaches the target through `requires`
   edges) → error listing them, exit 1.
2. Writes `is_active = false`. **Data is always kept** (MVP): the plugin's
   tables remain untouched.
3. `--rollback` additionally runs `migrate:rollback --path=<plugin>/migrations
   --force`, dropping the plugin's tables. A plugin with no migrations dir
   reports nothing instead.

### Cache

The app does not use config/route caching in dev (no `bootstrap/cache/config
.php` / routes manifest), so activate/deactivate need no cache invalidation.
If an environment ever enables `config:cache`/`route:cache`, those caches
must be rebuilt after any activation change.

## Scoped Migrations (P4-05)

Migrations live in the plugin's `migrations` directory (`database/migrations`
by default) and are executed by the standard migrator, recorded in the shared
`migrations` table:

- First activation: `migrate --path=platform/plugins/<slug>/database/migrations --force`.
- `--rollback`: `migrate:rollback --path=<same> --force`.
- A plugin without a migrations directory skips the step with a "no
  migrations" note — never an error.

Because the standard migrator records the files, re-activation is a no-op and
deactivation + re-activation keeps existing data.

## The PluginActivator Service (P4-06/P4-07)

`Sitewyn\Core\Base\Support\PluginActivator` (singleton) is the single
implementation of the lifecycle rules — both `plugin:activate`/
`plugin:deactivate` and the admin UI call it, so the two frontends can never
drift apart:

- `activate(string $slug, bool $runMigrations = true): void`
- `deactivate(string $slug, bool $rollback = false): void`

Failures are thrown, not returned:

| Exception | Meaning |
| --- | --- |
| `PluginNotFoundException` | slug not discoverable (UI → 404, CLI → error) |
| `PluginDependencyException` | dependency rules forbid the action (UI → error flash, CLI → error) |
| `PluginMigrationFailedException` | scoped migrations/rollback failed (UI → error flash, CLI → error) |

The exception carries `slug` and `related` (the offending slugs) so callers
can react to the data, not just the message.

## Dependencies (P4-07)

Rules enforced by `PluginActivator`, identical for CLI and UI:

- **Activation** requires every slug in `requires` to be *available*
  (discoverable on disk) and *active*. Nothing is auto-activated: the error
  spells out what is missing (`Plugin [demo-dependant] requires
  [demo-plugin] which is inactive. Activate it first.`), so chains are
  activated bottom-up.
- **Circular `requires` chains** (A requires B, B requires A) can never be
  activated bottom-up, so any plugin that sits on such a cycle is rejected
  up-front with `Circular dependency detected: a → b → a.` The detector
  depth-first walks `requires` edges of available plugins and only fires
  when the walk leads back to the target itself; a merely *reachable* cycle
  is that dependency's own problem and is reported when it is activated.
- **Deactivation** is blocked while the *transitive* closure of active
  dependents is non-empty: any active plugin that reaches the target by
  following `requires` edges backwards through active plugins. The whole
  set is listed in the error (`required by active plugin(s): a, b.`), and
  nothing is cascade-deactivated.

These rules keep the invariant "an active plugin's requirements are active"
true at all times, which is what makes boot-time gating (P4-03) safe.

## Core Modules as Plugins (P4-08)

Page and Blog are now managed plugins while staying exactly where they were:

- `platform/packages/page/plugin.json` — slug `page`, provider
  `Sitewyn\Packages\Page\Providers\PageServiceProvider`, `requires: []`.
- `platform/packages/blog/plugin.json` — slug `blog`, provider
  `Sitewyn\Packages\Blog\Providers\BlogServiceProvider`, `requires: []`
  (blog does not use any page code, so there is no dependency to declare).

Nothing else about the packages changed: same directory, same namespace,
same composer.json (its `extra.laravel.providers` stays as the
invalid-manifest fallback described above), same routes and views. The
manifest only adds the management surface: `plugin:list` shows them
(source `package`), the admin UI lists them, and deactivation now removes
their routes, menu entries and permissions from the running app while
keeping every row in `pages`/`posts` — re-activation restores the module
with its data intact.

- **Media deliberately ships no manifest** (P4-08 decision, revisited in
  P5): media is core-ish — uploads are referenced from posts/pages by URL,
  and a half-deleted media module would leave the content packages with
  broken asset links. It stays manifest-less and always loaded.
- **Migration flow on already-migrated installs:** every pre-plugin-era
  database already ran the page/blog migrations globally (they are recorded
  in the `migrations` table). The first `plugin:activate` therefore runs the
  scoped `migrate --path` and executes *zero* pending files — activation is
  a pure state write. Deactivation keeps the tables; only `--rollback`
  (CLI-only) would drop them.

End-to-end lifecycle tests live in
`tests/Feature/PackagePluginLifecycleTest.php`.

## Version Constraints (P4-09)

`requires` may map a dependency slug to a version constraint that is checked
against the dependency's **manifest version** at activation time:

```json
{
    "name": "Comments",
    "slug": "comments",
    "version": "1.0.0",
    "requires": {"blog": "^1.0"}
}
```

Supported formats — deliberately dependency-free, no composer-semver:

| Constraint | Matches | Does not match |
| --- | --- | --- |
| `"1.0.0"` (exact) | exactly `1.0.0` | anything else |
| `"^1.0"` / `"^1.0.0"` (caret prefix) | `1.0` itself and `1.0.x` | `1.4.2`, `2.0.0` |

Known limitations (accepted for P4): the caret is a *narrow prefix match* —
`^1.0` covers only `1.0.*`, not the whole `1.x` range real semver would —
and there are no comparison operators, ranges or wildcards (`~`, `>=`, `*`).
Anything else **fails closed**: an unsupported constraint simply never
matches, so a typo surfaces as an activation error naming the constraint
(`Supported constraints: exact "1.2.3" or "^1.2".`) instead of being
silently ignored. The plain slug list stays unconstrained, so existing
manifests (e.g. `demo-dependant`) behave exactly as in P4-07.

## Admin UI (P4-06)

`GET /admin/plugins` (`admin.plugins.index`) renders
`core/base::admin.plugins.index`: name + slug, description, version, a
source badge (`plugin`/`package`), an Active/Inactive status badge, and a
per-row action — Activate (plain POST form) for inactive plugins,
Deactivate (POST behind an `x-admin-modal` confirm) for active ones. The UI
has no create/edit: plugins are discovered on disk, never typed in.

Actions post to `admin.plugins.activate` /
`admin.plugins.deactivate` (`POST /admin/plugins/{slug}/…`) and are handled
by `PluginManageController`, which flashes `admin_flash` after every action:

- unknown slug → 404;
- already in the target state → info flash, no write;
- dependency/migration failure from `PluginActivator` → error flash with
  the exception message, row untouched;
- success → success flash. The UI always keeps plugin data — `--rollback`
  stays CLI-only.

All three routes are guarded by the `plugins.manage` permission (group
`plugins`, module `core/base`), which also gates the "Plugins" sidebar entry
(icon `plugin`, order 85 — just before Settings at 90, keeping system-level
entries grouped at the bottom). Plugin management is core infrastructure,
so the controller and view live in `platform/core/base`.

## Sample Plugins

`platform/plugins/demo-plugin` and `platform/plugins/demo-dependant` are
**fixtures** (used by `tests/Feature/PluginLifecycleTest.php`) and can be
copied as the starting shape for a real plugin:

- `demo-plugin`: full manifest (`provider` + `autoload` + `migrations`),
  a provider registering `GET /demo-plugin/health`, and a migration creating
  the `demo_plugins` table.
- `demo-dependant`: manifest only, `requires: ["demo-plugin"]`, no provider
  or migrations — the minimal "dependent" plugin.

The now-real page/blog plugins (`platform/packages/*/plugin.json`) show the
same shape for modules that live on the composer autoloader.

## P4 Roadmap (closed — 9/9)

- **P4-06** (done): `/admin/plugins` management UI.
- **P4-07** (done): full `requires` dependency enforcement — activation
  checks, transitive dependent protection, circular-chain detection.
- **P4-08** (done): Page/Blog converted to standard plugins via manifests;
  media intentionally left manifest-less (P5 decision).
- **P4-09** (done): end-to-end page/blog lifecycle tests plus light version
  constraints (`^major.minor` prefix + exact, fail-closed on unknown
  formats). Not in scope yet, candidates for P5: full semver constraints,
  auto-activation of requirement chains, and the media manifest decision.
