<?php

namespace Modules\Glitter\Reservation\Actions;

use Modules\Glitter\Reservation\Enums\BookingStatus;
use Modules\Glitter\Reservation\Models\ReservationBooking;

class BookingStatusChangedAction
{
    public function __construct(
        private DispatchBookingNotificationAction $dispatchBookingNotificationAction,
    ) {}

    public function handle(ReservationBooking $booking, string $fromStatus, string $toStatus): void
    {
        $targetStatus = BookingStatus::from($toStatus);

        $eventType = $this->resolveEventType($targetStatus);

        if ($eventType === null) {
            return;
        }

        $this->dispatchBookingNotificationAction->handle(
            $booking,
            $eventType,
            $fromStatus,
            $targetStatus->value,
            [
                'admin_memo' => $booking->admin_memo,
            ],
        );
    }

    private function resolveEventType(BookingStatus $targetStatus): ?string
    {
        return match ($targetStatus) {
            BookingStatus::Confirmed => 'booking_confirmed',
            BookingStatus::Cancelled => 'admin_cancelled',
            BookingStatus::Completed => 'booking_completed',
            BookingStatus::NoShow => 'booking_no_show',
            BookingStatus::Pending => null,
        };
    }
}
