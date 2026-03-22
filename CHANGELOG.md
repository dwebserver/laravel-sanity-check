# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- GitHub community templates (bug report, feature request, pull request), Dependabot for Actions, maintainer release checklist in `docs/releasing.md`, README badge placeholders, and `.editorconfig` for consistent formatting.

## [1.0.0] - 2026-03-22

### Added

- Core engine: `RouteScanner`, `RouteFilter`, `RouteTester`, `ParameterResolutionManager`, `ResponseClassifier`, `ResultAggregator`, `RunOrchestrator`, `ResultRepository`, `JsonExporter`, and related contracts/DTOs.
- Publishable `config/sanity-check.php` with documented options, execution modes, export toggles, UI settings, and `services.bindings` overrides.
- Admin dashboard, `sanity-check:run` Artisan command, JSON/CSV export, route scanning, parameter resolvers, optional persistence with retention, and automated tests.
- Packaged as **dwebserver/laravel-sanity-check** with PHP namespace `DynamicWeb\SanityCheck` ([repository](https://github.com/dwebserver/laravel-sanity-check)).

### Changed

- Config keys aligned with current behavior (`middleware`, `authorization_ability`, production safety flags, etc.).

### Fixed

- Retention pruning loads only the newest `max_saved_runs` row IDs (avoids loading every run ID into memory).
- Dashboard **Run** and Artisan `sanity-check:run` catch failures, `report()` them, and return a safe HTTP redirect + flash message or CLI error line.
- `sanity-check.services.bindings` validates abstract (interface/class) and concrete (class) at registration.
- `SanityCheckRun::$success_rate` uses an Eloquent `float` cast.

[Unreleased]: https://github.com/dwebserver/laravel-sanity-check/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/dwebserver/laravel-sanity-check/releases/tag/v1.0.0
