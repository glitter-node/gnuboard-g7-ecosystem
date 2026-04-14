<?php

return [
    'pagination' => [
        'per_page' => 20,
        'max_per_page' => 100,
    ],
    'public_route_prefix' => '/reservation',
    'public_lookup_page_path' => '/reservation/lookup',
    'public_apply_page_path' => '/reservation/apply',
    'admin_page_path' => '/admin/reservations',
    'email_verification' => [
        'enabled' => true,
        'required_for_booking' => true,
        'verify_ttl_minutes' => 15,
        'access_ttl_minutes' => 30,
        'session_key' => 'reservation_email_verification',
        'cookie_key' => 'reservation_email_verification',
        'success_redirect_path' => '/reservation/apply',
    ],
    'notification' => [
        'enabled' => true,
        'dispatcher_binding_optional' => true,
        'default_channels' => [],
        'template_keys' => [
            'booking_created' => 'reservation.booking_created',
            'booking_confirmed' => 'reservation.booking_confirmed',
            'admin_cancelled' => 'reservation.admin_cancelled',
            'customer_cancelled' => 'reservation.customer_cancelled',
            'booking_completed' => 'reservation.booking_completed',
            'booking_no_show' => 'reservation.booking_no_show',
        ],
    ],
    'booking' => [
        'lookup_identifier' => 'customer_phone',
        'customer_cancel_allowed_statuses' => [
            'pending',
            'confirmed',
        ],
    ],
    'ui' => [
        'enable_apply_layout' => true,
        'enable_lookup_layout' => true,
    ],
    'admin' => [
        'enable_booking_list_layout' => true,
    ],
];
