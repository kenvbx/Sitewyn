# Naming Rules

This project uses one clear naming system for PHP namespaces, Composer packages,
frontend packages, routes, permissions, and modules.

## Root Application

- Composer package: `sitewyn/cms`
- Laravel host namespace: `App`
- CMS module namespace root: `Sitewyn`

The `App` namespace is reserved for Laravel host bootstrapping and project-level
integration. Reusable CMS logic should live under `platform/` and use the
`Sitewyn` namespace.

## PHP Namespaces

Use this pattern:

```text
Sitewyn\<Layer>\<Module>
```

Examples:

- `Sitewyn\Core\Base`
- `Sitewyn\Core\Acl`
- `Sitewyn\Packages\Page`
- `Sitewyn\Packages\Slug`
- `Sitewyn\Plugins\Blog`
- `Sitewyn\Themes\DefaultTheme`

Provider classes should use:

```text
Sitewyn\<Layer>\<Module>\Providers\<Module>ServiceProvider
```

Example:

```text
Sitewyn\Core\Base\Providers\BaseServiceProvider
```

## Composer Package Names

Use this pattern:

```text
sitewyn/<layer>-<module>
```

Examples:

- `sitewyn/core-base`
- `sitewyn/core-acl`
- `sitewyn/package-page`
- `sitewyn/package-slug`
- `sitewyn/plugin-blog`
- `sitewyn/theme-default`

## Frontend Package Names

Use this pattern:

```text
@sitewyn/<layer>-<module>
```

Examples:

- `@sitewyn/core-base`
- `@sitewyn/package-page`
- `@sitewyn/plugin-blog`
- `@sitewyn/theme-default`

## Route Names

Admin route names should be grouped by module:

```text
admin.<module>.<action>
```

Examples:

- `admin.users.index`
- `admin.system.roles.edit`
- `admin.pages.create`
- `admin.media.index`

Public routes should avoid the `admin` prefix:

```text
pages.show
posts.show
```

## Permission Keys

Use this pattern:

```text
<module>.<action>
```

Examples:

- `users.index`
- `users.create`
- `roles.edit`
- `media.destroy`
- `pages.publish`

Keep permission keys stable. Renaming permission keys should be treated as a
data migration, not a casual refactor.

## Filesystem Names

Use kebab-case for module folders:

```text
platform/core/base
platform/core/acl
platform/packages/page
platform/plugins/blog
platform/themes/default
```

Use PascalCase for PHP classes and camelCase for JavaScript variables/functions.
