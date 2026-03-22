# Contributing

Thank you for helping improve **dwebserver/laravel-sanity-check**.

This project adheres to the [Code of Conduct](CODE_OF_CONDUCT.md). By participating, you agree to uphold it.

## Requirements

- PHP 8.1+ (aligned with `composer.json`)
- Composer 2.x
- A Laravel version compatible with the `illuminate/*` constraints in `composer.json`

## Issues and pull requests

- **Bugs and features:** use the [bug report](.github/ISSUE_TEMPLATE/bug_report.yml) and [feature request](.github/ISSUE_TEMPLATE/feature_request.yml) forms so we get versions and reproduction steps. For sensitive reports, use [SECURITY.md](SECURITY.md) instead of a public issue.
- **Pull requests:** GitHub shows [the PR template](.github/pull_request_template.md) when you open a PR—fill it in so reviewers can see scope, risk, and testing notes quickly.

## Workflow

1. Fork the repository and create a feature branch from `main`.
2. Make focused changes with clear commit messages.
3. Add or update **tests** for any behavior change:
   - `tests/Unit` for pure logic and small components
   - `tests/Feature` for HTTP, Artisan, DB, and orchestration flows
4. Run the quality checks below locally.
5. Open a pull request describing motivation, changes, and any rollout or risk notes.

## Checks

```bash
composer validate --strict
composer install
composer run lint      # Laravel Pint (dry run)
composer run analyse   # PHPStan on src/
composer test          # PHPUnit
```

To apply Pint fixes:

```bash
composer run format
```

## Code style

- Match existing layout (`src/Contracts`, `src/Services`, etc.).
- Prefer small, reviewable diffs; avoid mixing wide refactors with feature work.
- Use `declare(strict_types=1);` in new PHP files.

## Security

If you believe you have found a security vulnerability, follow [SECURITY.md](SECURITY.md) instead of opening a public issue.
