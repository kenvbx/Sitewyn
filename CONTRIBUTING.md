# Contributing

## Commit Message Convention

Use short, focused commit messages:

```text
chore: initialize project repository
feat: add admin layout
fix: repair test script
docs: update module guide
test: add smoke coverage
```

Rules:

- Use English, imperative wording.
- Keep the subject under 72 characters when possible.
- Avoid reference product names and assistant/tool names.
- Do not mix unrelated changes in one commit.

## Before Committing

Run:

```bash
composer test
npm run build
```

For admin UI work, also verify the screen visually and keep Tabler layout intact.
