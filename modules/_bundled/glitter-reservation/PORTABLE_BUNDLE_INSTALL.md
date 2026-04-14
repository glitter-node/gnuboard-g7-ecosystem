# Portable Bundle Install

## Installation Steps

1. Place the module at `modules/glitter-reservation`.
2. Ensure the host project can discover and load the module.
3. Regenerate extension caches if the host project requires it.
4. Run the portable migration set.
5. Seed or create reservation services and schedules.
6. Map the default layouts into host template routes.
7. Optionally bind a notification dispatcher.
8. Verify public and admin flows.

## Migration Steps

Run only the portable migration set:

```bash
/usr/local/bin/php83 artisan migrate --path=modules/glitter-reservation/database/migrations
```

Do not run files from `database/migrations_excluded/` for a portable install.

## Host Config Expectations

The host project should provide or confirm these config values:

- `reservation.public_route_prefix`
- `reservation.public_lookup_page_path`
- `reservation.public_apply_page_path`
- `reservation.admin_page_path`
- `reservation.email_verification.enabled`
- `reservation.email_verification.required_for_booking`
- `reservation.email_verification.verify_ttl_minutes`
- `reservation.email_verification.access_ttl_minutes`
- `reservation.email_verification.session_key`
- `reservation.email_verification.cookie_key`
- `reservation.email_verification.success_redirect_path`
- `reservation.notification.enabled`
- `reservation.notification.dispatcher_binding_optional`
- `reservation.notification.default_channels`
- `reservation.notification.template_keys`
- `reservation.booking.lookup_identifier`
- `reservation.booking.customer_cancel_allowed_statuses`
- `reservation.ui.enable_apply_layout`
- `reservation.ui.enable_lookup_layout`
- `reservation.admin.enable_booking_list_layout`

## Template Route Mapping Expectations

The host project must expose these layouts through template `routes.json` files if it wants them available as pages:

- `glitter-reservation.reservation_apply`
- `glitter-reservation.reservation_lookup`
- `glitter-reservation.reservation_booking_list`

The host project owns the final path mapping.

Example expectations:

- map a public apply page path
- map a public lookup page path
- map an admin booking list page path

## Admin Exposure Expectations

The bundle provides admin APIs and menu metadata.

The host project is responsible for:

- choosing the final admin page path
- mapping the admin layout into the host admin template
- ensuring the current user has the required permissions

## Optional Notification Binding Expectations

The bundle works without a notification implementation.

If the host wants delivery behavior, it must bind `NotificationDispatcherInterface` to a concrete implementation.

Possible implementations:

- host application provider binding
- plugin provider binding

## Required Initial Data

Before public booking is usable, the host project must provide:

- at least one active reservation service
- valid reservation schedules for that service
- optional holidays when needed

## Post-Install Checklist

- module is discoverable by the host project
- portable migrations have run successfully
- excluded migrations were not run
- reservation services exist
- reservation schedules exist
- public apply layout is mapped when needed
- public lookup layout is mapped when needed
- admin booking list layout is mapped when needed
- email verification flow works when enabled
- booking creation works
- booking lookup works
- booking cancellation works
- admin booking detail and status update work
- notification fallback works without a dispatcher binding
