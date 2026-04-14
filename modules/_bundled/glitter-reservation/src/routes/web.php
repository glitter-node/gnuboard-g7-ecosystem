<?php

use Illuminate\Support\Facades\Route;
use Modules\Glitter\Reservation\Http\Controllers\ReservationController;
use Modules\Glitter\Reservation\Http\Middleware\EnsureReservationEmailVerified;

Route::prefix('reservation')
    ->name('reservation.')
    ->group(function () {
        Route::get('/services', [ReservationController::class, 'services'])
            ->middleware('throttle:30,1')
            ->name('services.index');

        Route::post('/email-verifications', [ReservationController::class, 'requestEmailVerification'])
            ->middleware('throttle:5,1')
            ->name('email-verifications.store');

        Route::get('/email-verifications/verify', [ReservationController::class, 'verifyEmailVerification'])
            ->middleware('throttle:30,1')
            ->name('email-verifications.verify');

        Route::get('/verification-status', [ReservationController::class, 'verificationStatus'])
            ->middleware('throttle:60,1')
            ->name('verification-status.show');

        Route::get('/bookings/lookup', [ReservationController::class, 'lookupBookings'])
            ->name('bookings.lookup');

        Route::post('/bookings/{booking}/cancel', [ReservationController::class, 'cancelBooking'])
            ->name('bookings.cancel');

        Route::get('/slots', [ReservationController::class, 'slots'])
            ->name('slots.index');

        Route::post('/bookings', [ReservationController::class, 'bookings'])
            ->middleware(EnsureReservationEmailVerified::class)
            ->name('bookings.store');
    });
