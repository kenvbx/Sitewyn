# Sitewyn CMS

Personal CMS built on Laravel.

## Development

```bash
composer install
npm install
php artisan migrate
composer test
npm run build
```

## Architecture Direction

- Keep CMS logic in a modular `platform/` structure.
- Use `platform/core` for required foundation modules.
- Use `platform/packages` for shared CMS capabilities.
- Use `platform/plugins` for optional product features.
- Use `platform/themes` for frontend themes.
- Keep admin UI consistent with Tabler.
- Prioritize familiar CMS workflows first: posts, pages, media, taxonomy, menus, themes, widgets, users, roles, and settings.

## Commit Rules

- Use short English commit messages in imperative style.
- Prefer prefixes: `chore:`, `feat:`, `fix:`, `docs:`, `test:`, `refactor:`.
- Do not include reference product names or assistant/tool names in commit messages.
- Keep commits focused and easy to review.
