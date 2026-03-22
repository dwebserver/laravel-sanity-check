# Release checklist

Use this before tagging a stable or pre-release version. Adjust branch names (`main` / `master`) and remotes to match your fork or organization.

## Pre-release

1. **Branch** — Work from a clean branch (e.g. `release/x.y.z` or `main`) with no unintended local changes (`git status`).
2. **Changelog** — Add a dated section under `[Unreleased]` in [`CHANGELOG.md`](../CHANGELOG.md), then move it under the new version heading (Keep a Changelog style). List **Added**, **Changed**, **Deprecated**, **Removed**, **Fixed**, **Security** as appropriate.
3. **Version semantics** — Confirm **SemVer**: breaking API/config → major; backward-compatible features → minor; fixes → patch.
4. **Docs** — Update [`README.md`](../README.md) if install steps, defaults, or badges change.
5. **Quality gates** (from repo root):

   ```bash
   composer validate --strict
   composer install
   composer run lint
   composer run analyse
   composer test
   ```

6. **Composer** — Ensure [`composer.json`](../composer.json) `name`, `support`, and `homepage` match the public Git URL and Packagist package name.
7. **Security** — No secrets, tokens, or internal URLs committed. Confirm [`SECURITY.md`](../SECURITY.md) contact details are current.

## Tag and push

1. **Commit** release prep:

   ```bash
   git add -A
   git commit -m "Prepare release vX.Y.Z"
   ```

2. **Tag** (annotated tag recommended):

   ```bash
   git tag -a vX.Y.Z -m "vX.Y.Z"
   ```

3. **Push** commits and tag:

   ```bash
   git push origin main
   git push origin vX.Y.Z
   ```

## Post-release

1. **GitHub** — Confirm [Actions](https://github.com/dwebserver/laravel-sanity-check/actions) completed for the tag (e.g. release workflow artifacts if enabled).
2. **GitHub Release** — Edit the generated release notes if needed; attach any extra assets required by your process.
3. **Packagist** — Verify the new version appears (webhook from GitHub, or manual update). Composer normalizes `vX.Y.Z` to `X.Y.Z`.
4. **Announce** — Blog, changelog tweet, or internal comms per your policy.

## Hotfix from a tag

1. Branch from the tag: `git checkout -b hotfix/vX.Y.Z+1 vX.Y.Z`
2. Fix, changelog entry, tests, tag `vX.Y.Z+1`, push tag.
3. Merge or cherry-pick back to `main` as appropriate.

## Pre-releases

- Use tags like `v2.0.0-beta1` if Composer stability (`minimum-stability` / `@beta`) is acceptable for your consumers.
- Document expectations in the GitHub Release and changelog.
