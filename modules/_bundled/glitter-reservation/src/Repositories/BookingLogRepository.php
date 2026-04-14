<?php

namespace Modules\Glitter\Reservation\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Glitter\Reservation\Enums\BookingStatus;
use Modules\Glitter\Reservation\Models\ReservationBooking;
use Modules\Glitter\Reservation\Models\ReservationBookingLog;

class BookingLogRepository
{
    public function create(array $data): ReservationBookingLog
    {
        return ReservationBookingLog::query()->create($data);
    }

    public function createCustomerCreatedLog(ReservationBooking $booking, array $payload = []): ReservationBookingLog
    {
        return $this->create([
            'reservation_booking_id' => $booking->getKey(),
            'event_type' => 'customer_created',
            'from_status' => null,
            'to_status' => BookingStatus::Pending->value,
            'description' => 'customer_created',
            'payload' => $payload,
            'logged_by' => null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function createStatusChangedLog(
        ReservationBooking $booking,
        ?BookingStatus $fromStatus,
        BookingStatus $toStatus,
        ?int $actorUserId = null,
        array $payload = [],
    ): ReservationBookingLog {
        return $this->create([
            'reservation_booking_id' => $booking->getKey(),
            'event_type' => 'status_changed',
            'from_status' => $fromStatus?->value,
            'to_status' => $toStatus->value,
            'description' => 'status_changed',
            'payload' => $payload,
            'logged_by' => $actorUserId,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function createStatusLog(
        int $bookingId,
        ?string $fromStatus,
        string $toStatus,
        ?int $actorUserId = null,
        ?string $note = null,
        array $payload = [],
    ): ReservationBookingLog {
        return $this->create([
            'reservation_booking_id' => $bookingId,
            'event_type' => 'status_changed',
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'description' => $note,
            'payload' => $payload === [] ? null : $payload,
            'logged_by' => $actorUserId,
        ]);
    }

    public function createCustomerCancelledLog(
        int $bookingId,
        string $fromStatus,
        ?string $customerPhone = null,
    ): ReservationBookingLog {
        return $this->createStatusLog(
            $bookingId,
            $fromStatus,
            BookingStatus::Cancelled->value,
            null,
            'customer_cancelled',
            $customerPhone === null ? [] : ['customer_phone' => $customerPhone]
        );
    }

    /**
     * @return Collection<int, ReservationBookingLog>
     */
    public function findByBookingId(int $bookingId): Collection
    {
        return ReservationBookingLog::query()
            ->where('reservation_booking_id', $bookingId)
            ->orderBy('created_at')
            ->get();
    }
}
