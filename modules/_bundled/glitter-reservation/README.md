# Glitter Reservation Module

## Portable Bundle Scope

`glitter-reservation` is now scoped as a portable Gnuboard 7 reservation bundle.

The portable bundle includes:

- reservation domain models, repositories, services, controllers, and requests
- public and admin API routes
- default overridable layouts
- portable config
- portable migration set
- optional notification abstraction

The portable bundle does not own final public page URLs, final admin page URLs, or project-specific academy fields.

## Bundled Convention Alignment

The module now aligns its bundled structure in these areas:

- route files live under `src/routes/*`
- provider no longer handles migration loading directly
- canonical PHP translations live under `resources/lang/{locale}/*`
- build scaffold is present under `vite.config.ts`, `resources/js/*`, and `resources/css/*`
- dist placeholders are present under `dist/js/*` and `dist/css/*`
- install seeder scaffold is present under `database/seeders/*`
- upgrade scaffold is present under `upgrades/*`

The current packaging step completes the bundled-module scaffold target without changing runtime behavior.

## Host Responsibilities

The host project is responsible for:

- enabling the module in the host project
- exposing user pages through template route mapping
- exposing the admin page through template route mapping
- providing initial reservation services and schedules
- providing mail, session, and auth environment required by the current flows
- deciding final page URLs for lookup, apply, and admin views
- regenerating extension caches after install or update

## Optional Integrations

Optional integrations remain host-owned:

- notification delivery implementations
- custom template overrides
- host-specific admin path conventions
- host-specific page path conventions

## Required Host Template Route Mapping

The bundle ships default layouts but does not force final page exposure.

The host project must map these layouts explicitly:

- `glitter-reservation.reservation_apply`
- `glitter-reservation.reservation_lookup`
- `glitter-reservation.reservation_booking_list`

The host project must choose final page paths and connect them in its template `routes.json` files.

## Required Initial Data

The bundle requires host-provided reservation data before public booking can work:

- active rows in `reservation_services`
- valid rows in `reservation_schedules`
- optional rows in `reservation_holidays`

Without service and schedule data, the public booking flow has no available slots.

## Portable Migration Set

Portable bundle installs should run only the migrations in `database/migrations/`.

Current portable migration set:

- `2026_04_06_000001_create_reservation_services_table.php`
- `2026_04_06_000002_create_reservation_schedules_table.php`
- `2026_04_06_000003_create_reservation_holidays_table.php`
- `2026_04_06_000004_create_reservation_bookings_table.php`
- `2026_04_06_000005_create_reservation_booking_logs_table.php`
- `2026_04_06_000007_add_break_times_to_reservation_schedules_table.php`
- `2026_04_06_000008_add_completed_at_to_reservation_bookings_table.php`
- `2026_04_06_000010_alter_customer_email_length_in_reservation_bookings_table.php`
- `2026_04_06_000011_create_reservation_email_verifications_table.php`

## Excluded Migrations

The following files are excluded from the portable bundle migration set and remain in `database/migrations_excluded/`:

- `2026_04_06_000006_drop_legacy_reservations_table_if_exists.php`
- `2026_04_06_000009_add_student_grade_to_reservation_bookings_table.php`
- `2026_04_07_000012_backfill_weekday_reservation_schedules.php`

These files are retained only for historical host compatibility and are not part of the portable install contract.

## Notification Integration Contract

The bundle depends only on `NotificationDispatcherInterface`.

Default behavior:

- `NullNotificationDispatcher` is used when no delivery implementation is bound
- booking flows continue to work without a notification plugin

Host integration options:

- bind a host-specific dispatcher in the host project
- bind a plugin-provided dispatcher in a plugin or provider

Current event names:

- `booking_created`
- `booking_confirmed`
- `admin_cancelled`
- `customer_cancelled`
- `booking_completed`
- `booking_no_show`

## Versioning and Releases

The current portable release target is `0.1.0`.

Release preparation is documented in `RELEASING.md`.

Use `VERSION`, `module.json`, and `CHANGELOG.md` together when preparing a GitHub distribution update.

## Bundled Packaging Scaffold

The module now includes safe packaging placeholders for bundled distribution:

- `vite.config.ts`
- `resources/js/index.ts`
- `resources/css/main.css`
- `dist/js/module.iife.js`
- `dist/css/module.css`
- `database/seeders/DatabaseSeeder.php`
- `database/seeders/ReservationInstallSeeder.php`
- `upgrades/Upgrade_0_1_0.php`

These files are packaging scaffolds only and do not change reservation runtime paths, route behavior, service behavior, or host integration behavior.

## Runtime Commands

Run portable bundle migrations:

```bash
/usr/local/bin/php83 artisan migrate --path=modules/glitter-reservation/database/migrations
```

Run module tests when the host project supports the test command:

```bash
/usr/local/bin/php83 artisan test modules/glitter-reservation/tests
```

See `PORTABLE_BUNDLE_INSTALL.md` for the full installation and host integration contract.
