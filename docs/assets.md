# Asset Build

The root Vite config discovers frontend entries in the main application and in
modular platform folders.

Supported module entry names:

- `resources/css/app.css`
- `resources/css/admin.css`
- `resources/css/theme.css`
- `resources/js/app.js`
- `resources/js/admin.js`

Supported module roots:

- `platform/core/*`
- `platform/packages/*`
- `platform/plugins/*`
- `platform/themes/*`

Admin UI assets start in `platform/core/base/resources` and load Tabler as the
baseline interface library. Keep future admin screens aligned with Tabler
markup, spacing, components, and responsive behavior.

`platform/themes/*` uses the `theme.css` entry: each theme ships one frontend
stylesheet (e.g. `platform/themes/default/resources/css/theme.css`, built into
the Vite manifest and loaded by the theme layout via `@vite`). See
`docs/themes.md`.
