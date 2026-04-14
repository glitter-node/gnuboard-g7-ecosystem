# Releasing

## Versioning Strategy

This module follows semantic versioning.

- patch versions are for safe fixes and release metadata updates
- minor versions are for backward-compatible feature additions
- major versions are for breaking portable contract changes

## Update VERSION

1. Update `VERSION` with the next release number.
2. Keep the file content to a single version line.

## Update module.json Version

1. Update `module.json` `version`.
2. Keep `VERSION` and `module.json` synchronized.

## Update CHANGELOG.md

1. Add or update the release entry in `CHANGELOG.md`.
2. Summarize release-facing changes only.
3. Mention portable boundary, migration scope, and host integration changes when relevant.

## Prepare a Git Tag

1. Confirm `VERSION`, `module.json`, and `CHANGELOG.md` match.
2. Commit the release preparation changes.
3. Prepare a Git tag in the form `glitter-reservation-vX.Y.Z`.

This document does not create tags automatically.

## Prepare a GitHub Release

1. Use `.github/release_template.md` as the release body template.
2. Set the GitHub release title to match the module version.
3. Link the matching Git tag.
4. Paste the release summary and migration notes.
5. Include host integration notes when paths, templates, or config expectations changed.

This document does not create GitHub releases automatically.

## Verify Before Release

- `VERSION` matches `module.json`
- `CHANGELOG.md` contains the target version
- `README.md` reflects the current portable scope
- `PORTABLE_BUNDLE_INSTALL.md` reflects the current install contract
- `database/migrations/` contains only the portable migration set
- `database/migrations_excluded/` contains excluded historical migrations
- `vite.config.ts`, `resources/js/index.ts`, and `resources/css/main.css` are present
- `dist/js/module.iife.js` and `dist/css/module.css` are present
- `database/seeders/` contains the install seeder scaffold
- `upgrades/Upgrade_0_1_0.php` is present
- `package.json` and `module.json` are valid JSON
- key PHP files pass syntax checks
- layout JSON files pass JSON validation

## Portable Migration Release Rule

Files in `database/migrations_excluded/` are not part of portable release installs.

Portable release installs must run only `database/migrations/`.
