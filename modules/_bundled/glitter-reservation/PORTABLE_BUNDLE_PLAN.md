# Portable Bundle Plan

## Scope

`glitter-reservation` is finalized as a portable reusable Gnuboard 7 reservation bundle target.

The portable target keeps the reservation core and moves host-specific exposure, host-specific integrations, and historical project migrations outside the portable install contract.

## Final Portable Core

The portable bundle includes:

- `module.php`
- `module.json`
- `composer.json`
- `package.json`
- `vite.config.ts`
- `vitest.config.ts`
- `config/reservation.php`
- `resources/js/index.ts`
- `resources/css/main.css`
- `dist/js/module.iife.js`
- `dist/css/module.css`
- `database/seeders/DatabaseSeeder.php`
- `database/seeders/ReservationInstallSeeder.php`
- `upgrades/Upgrade_0_1_0.php`
- `src/routes/api.php`
- `src/routes/web.php`
- `src/Actions/*`
- `src/Contracts/*`
- `src/Data/*`
- `src/Enums/BookingStatus.php`
- `src/Enums/NotificationChannel.php`
- `src/Http/Controllers/*`
- `src/Http/Middleware/*`
- `src/Http/Requests/*`
- `src/Http/Resources/*`
- `src/Models/*`
- `src/Notifications/*`
- `src/Providers/ReservationServiceProvider.php`
- `src/Repositories/*`
- `src/Services/*`
- default layouts under `resources/layouts/*`
- canonical translations under `resources/lang/*`
- portable migrations under `database/migrations/*`

## Host Integration Boundary

The host project owns:

- public page exposure paths
- admin page exposure paths
- template `routes.json` entries
- final layout exposure decisions
- initial reservation services and schedules
- optional notification bindings
- host cache regeneration flow

## Removed From Portable Target

The portable target no longer includes:

- academy-specific `student_grade` handling in core request validation, service payloads, API resource output, and default layouts
- dead listener and dead request or enum files
- excluded historical migrations from the portable migration set

## Bundled Convention Alignment Status

Completed:

- dangerous migration exclusion
- route placement alignment into `src/routes/*`
- provider migration-loading alignment with `BaseModuleServiceProvider`
- canonical translation placement alignment into `resources/lang/{locale}/*`
- route exposure decoupling
- translation scaffold alignment
- build-readiness scaffold alignment
- packaged build scaffold alignment
- dist placeholder alignment
- seeder scaffold alignment
- upgrade scaffold alignment
- dead code cleanup
- academy-specific portable target cleanup
- install contract documentation

## Bundled Completion Level

The module now reaches the bundled packaging completion target for:

- build scaffold
- dist scaffold
- seeder scaffold
- upgrade scaffold

Remaining optional improvements are outside this packaging completion step:

- optional repository contract layer
- module-local layout rendering tests
