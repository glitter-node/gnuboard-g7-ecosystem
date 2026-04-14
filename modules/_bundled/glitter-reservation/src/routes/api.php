<?php

use Illuminate\Support\Facades\Route;
use Modules\Glitter\Reservation\Http\Controllers\Admin\HolidayController;
use Modules\Glitter\Reservation\Http\Controllers\Admin\ReservationController;

Route::prefix('admin/reservation/bookings')
    ->middleware(['auth:sanctum', 'admin', 'throttle:600,1'])
    ->name('admin.reservation.bookings.')
    ->group(function () {
        Route::get('/', [ReservationController::class, 'index'])
            ->middleware('permission:admin,reservation.reservations.read')
            ->name('index');

        Route::get('/{booking}', [ReservationController::class, 'show'])
            ->middleware('permission:admin,reservation.reservations.read')
            ->name('show');

        Route::patch('/{booking}/status', [ReservationController::class, 'updateStatus'])
            ->middleware('permission:admin,reservation.reservations.update')
            ->name('status.update');
    });

Route::prefix('admin/reservation/holidays')
    ->middleware(['auth:sanctum', 'admin', 'throttle:600,1'])
    ->name('admin.reservation.holidays.')
    ->group(function () {
        Route::get('/', [HolidayController::class, 'index'])
            ->middleware('permission:admin,reservation.reservations.read')
            ->name('index');

        Route::post('/', [HolidayController::class, 'store'])
            ->middleware('permission:admin,reservation.reservations.create')
            ->name('store');

        Route::put('/{holiday}', [HolidayController::class, 'update'])
            ->middleware('permission:admin,reservation.reservations.update')
            ->name('update');

        Route::delete('/{holiday}', [HolidayController::class, 'destroy'])
            ->middleware('permission:admin,reservation.reservations.delete')
            ->name('destroy');
    });
