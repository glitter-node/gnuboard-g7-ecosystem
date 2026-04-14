# Portable Bundle Todo

## Completed

- excluded dangerous migrations from the portable migration set
- excluded the academy-specific migration from the portable migration set
- decoupled route exposure from hardcoded public and admin page paths
- aligned route placement to `src/routes/*`
- aligned provider behavior to bundled migration-loading conventions
- aligned canonical PHP translations to `resources/lang/{locale}/*`
- added `package.json`
- added `vite.config.ts`
- added `vitest.config.ts`
- added `resources/js/index.ts`
- added `resources/css/main.css`
- added `dist/js/module.iife.js`
- added `dist/css/module.css`
- added `database/seeders/DatabaseSeeder.php`
- added `database/seeders/ReservationInstallSeeder.php`
- added `upgrades/Upgrade_0_1_0.php`
- removed dead listener, dead request, and dead enum files
- removed `student_grade` from the portable bundle target
- completed the portable install and host integration contract

## Remaining Bundled Gaps

- add module-local layout rendering tests
- optionally add repository interface contracts for consistency with bundled reference modules

## Remaining Host-Side Work

- map default bundle layouts into host template `routes.json`
- provide initial reservation services and schedules
- bind an optional notification dispatcher when delivery behavior is required
- validate the chosen public and admin page paths in the host project

## Validation Checklist

- verify public booking flow still works in the host project
- verify admin booking flow still works in the host project
- verify email verification flow still works in the host project
- verify notification fallback still works without a dispatcher binding
- verify host layout exposure works through template routing
- verify excluded migrations are not required by the portable runtime
- verify `resources/lang/{locale}/*` is the canonical PHP translation location
- verify placeholder assets remain safe when unused
- verify no-op seeders and upgrade scaffold do not change runtime behavior

## Portable Bundle Exit Result

- route placement follows bundled-module structure
- provider migration loading follows bundled-module structure
- canonical PHP translations live under `resources/lang/{locale}/*`
- build scaffold is present
- dist placeholder scaffold is present
- seeder scaffold is present
- upgrade scaffold is present
- route exposure is host-owned
- admin exposure is host-owned
- `student_grade` is removed from the portable target
- dead code cleanup is completed
- portable install contract is completed
- bundled packaging scaffold is completed
