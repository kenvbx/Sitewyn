# Composer Modules

Root Composer is configured to recognize local CMS modules through path
repositories.

Supported module roots:

- `platform/core/*`
- `platform/packages/*`
- `platform/plugins/*`
- `platform/themes/*`

Each PHP module can provide its own `composer.json`. The root project includes
matching module files through Composer's merge plugin.

All module package names must use the `sitewyn/*` vendor prefix.

Minimum module package shape:

```json
{
    "name": "sitewyn/core-base",
    "type": "library",
    "autoload": {
        "psr-4": {
            "Sitewyn\\Core\\Base\\": "src/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "Sitewyn\\Core\\Base\\Providers\\BaseServiceProvider"
            ]
        }
    }
}
```

The first installed platform package is `sitewyn/core-base` at
`platform/core/base`.

After adding or changing module packages, run:

```bash
composer dump-autoload
composer test
```

Path repositories use symlinks so local module edits are reflected immediately
without publishing packages.
