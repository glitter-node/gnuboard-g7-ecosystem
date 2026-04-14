# Portable Bundle Boundary

## Must Stay Inside Portable Bundle

- `module.php`
- `module.json`
- `composer.json`
- `package.json`
- `vite.config.ts`
- `vitest.config.ts`
- `README.md`
- `PORTABLE_BUNDLE_INSTALL.md`
- `CHANGELOG.md`
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
- `resources/layouts/admin/reservation_booking_list.json`
- `resources/layouts/user/reservation_apply.json`
- `resources/layouts/user/reservation_lookup.json`
- `resources/lang/en/messages.php`
- `resources/lang/en/validation.php`
- `resources/lang/ko/messages.php`
- `resources/lang/ko/validation.php`
- `resources/lang/en.json`
- `resources/lang/ko.json`
- `src/Actions/BookingStatusChangedAction.php`
- `src/Actions/DispatchBookingNotificationAction.php`
- `src/Contracts/NotificationDispatcherInterface.php`
- `src/Data/NotificationMessageData.php`
- `src/Enums/BookingStatus.php`
- `src/Enums/NotificationChannel.php`
- `src/Http/Controllers/Admin/HolidayController.php`
- `src/Http/Controllers/Admin/ReservationController.php`
- `src/Http/Controllers/ReservationController.php`
- `src/Http/Middleware/EnsureReservationEmailVerified.php`
- `src/Http/Requests/AdminBookingListRequest.php`
- `src/Http/Requests/AdminHolidayStoreRequest.php`
- `src/Http/Requests/AdminHolidayUpdateRequest.php`
- `src/Http/Requests/CancelBookingRequest.php`
- `src/Http/Requests/LookupBookingRequest.php`
- `src/Http/Requests/SendReservationEmailVerificationRequest.php`
- `src/Http/Requests/SlotAvailabilityRequest.php`
- `src/Http/Requests/StoreReservationRequest.php`
- `src/Http/Requests/UpdateBookingStatusRequest.php`
- `src/Http/Resources/BookingDetailResource.php`
- `src/Http/Resources/BookingListResource.php`
- `src/Http/Resources/CustomerBookingLookupResource.php`
- `src/Http/Resources/HolidayResource.php`
- `src/Http/Resources/PublicReservationServiceResource.php`
- `src/Http/Resources/ReservationResource.php`
- `src/Models/ReservationBooking.php`
- `src/Models/ReservationBookingLog.php`
- `src/Models/ReservationEmailVerification.php`
- `src/Models/ReservationHoliday.php`
- `src/Models/ReservationSchedule.php`
- `src/Models/ReservationService.php`
- `src/Notifications/NullNotificationDispatcher.php`
- `src/Providers/ReservationServiceProvider.php`
- `src/Repositories/BookingLogRepository.php`
- `src/Repositories/BookingRepository.php`
- `src/Repositories/EmailVerificationRepository.php`
- `src/Repositories/HolidayRepository.php`
- `src/Repositories/ScheduleRepository.php`
- `src/Repositories/ServiceRepository.php`
- `src/Services/HolidayService.php`
- `src/Services/ReservationEmailVerificationService.php`
- `src/Services/ReservationService.php`
- `src/Services/SlotService.php`
- `database/migrations/2026_04_06_000001_create_reservation_services_table.php`
- `database/migrations/2026_04_06_000002_create_reservation_schedules_table.php`
- `database/migrations/2026_04_06_000003_create_reservation_holidays_table.php`
- `database/migrations/2026_04_06_000004_create_reservation_bookings_table.php`
- `database/migrations/2026_04_06_000005_create_reservation_booking_logs_table.php`
- `database/migrations/2026_04_06_000007_add_break_times_to_reservation_schedules_table.php`
- `database/migrations/2026_04_06_000008_add_completed_at_to_reservation_bookings_table.php`
- `database/migrations/2026_04_06_000010_alter_customer_email_length_in_reservation_bookings_table.php`
- `database/migrations/2026_04_06_000011_create_reservation_email_verifications_table.php`

## Must Become Config

- `public_route_prefix`
- `public_lookup_page_path`
- `public_apply_page_path`
- `admin_page_path`
- `email_verification.enabled`
- `email_verification.required_for_booking`
- `email_verification.verify_ttl_minutes`
- `email_verification.access_ttl_minutes`
- `email_verification.session_key`
- `email_verification.cookie_key`
- `email_verification.success_redirect_path`
- `notification.enabled`
- `notification.dispatcher_binding_optional`
- `notification.default_channels`
- `notification.template_keys`
- `booking.lookup_identifier`
- `booking.customer_cancel_allowed_statuses`
- `ui.enable_apply_layout`
- `ui.enable_lookup_layout`
- `admin.enable_booking_list_layout`

## Must Become Host Integration Responsibility

- host template route exposure for reservation pages
- host admin template route exposure for reservation admin page
- host path decisions for public reservation pages
- host path decisions for admin reservation pages
- host activation of bundle layouts in template `routes.json`
- host initial data provisioning for services and schedules
- host binding of notification delivery implementations
- host extension cache regeneration and install flow
- host-owned route injection for layout navigation targets

## Must Become Template Override Area

- `resources/layouts/user/reservation_apply.json`
- `resources/layouts/user/reservation_lookup.json`
- `resources/layouts/admin/reservation_booking_list.json`

## Removed From Portable Bundle Target

- `student_grade` handling in request validation
- `student_grade` handling in service payload creation
- `student_grade` output in API resources
- `student_grade` fields in default portable layouts
- `src/Listeners/ReservationActivityLogListener.php`
- `src/Http/Requests/UpdateReservationRequest.php`
- `src/Enums/ReservationStatus.php`
- root `routes/*` placement
- duplicate `src/lang/*` PHP translation tree

## Excluded From Portable Migration Loading

- `database/migrations_excluded/2026_04_06_000006_drop_legacy_reservations_table_if_exists.php`
- `database/migrations_excluded/2026_04_06_000009_add_student_grade_to_reservation_bookings_table.php`
- `database/migrations_excluded/2026_04_07_000012_backfill_weekday_reservation_schedules.php`

## Final Status

- route placement is aligned to `src/routes/*`
- admin exposure is host-owned
- public exposure is host-owned
- provider migration-loading alignment is completed
- canonical PHP translations now live under `resources/lang/{locale}/*`
- dead code cleanup is completed
- `student_grade` is removed from the portable target
- bundled packaging scaffold is completed
