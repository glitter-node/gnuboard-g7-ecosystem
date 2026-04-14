<?php

namespace Modules\Glitter\Reservation\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Modules\Glitter\Reservation\Enums\BookingStatus;
use Modules\Glitter\Reservation\Models\ReservationBooking;

class BookingRepository
{
    public function existsConfirmedOrPendingAt(int $serviceId, string $date, string $time): bool
    {
        return ReservationBooking::query()
            ->where('reservation_service_id', $serviceId)
            ->whereDate('booking_date', $date)
            ->whereTime('booking_time', $time)
            ->whereIn('status', [
                BookingStatus::Pending->value,
                BookingStatus::Confirmed->value,
            ])
            ->exists();
    }

    public function create(array $data): ReservationBooking
    {
        return ReservationBooking::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateForAdmin(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = ReservationBooking::query()
            ->with(['service', 'schedule'])
            ->orderByDesc('booking_date')
            ->orderByDesc('booking_time');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['service_id'])) {
            $query->where('reservation_service_id', (int) $filters['service_id']);
        }

        if (! empty($filters['booking_date'])) {
            $query->whereDate('booking_date', $filters['booking_date']);
        }

        if (! empty($filters['customer_phone'])) {
            $query->where('customer_phone', 'like', '%'.$filters['customer_phone'].'%');
        }

        return $query->paginate($perPage);
    }

    public function isDuplicateSlotException(QueryException $e): bool
    {
        $sqlState = (string) ($e->errorInfo[0] ?? $e->getCode() ?? '');
        $driverCode = (string) ($e->errorInfo[1] ?? '');
        $message = strtolower($e->getMessage());

        $constraintMatched = str_contains($message, 'reservation_bookings_service_date_time_unique')
            || str_contains($message, 'reservation_service_id')
                && str_contains($message, 'booking_date')
                && str_contains($message, 'booking_time');

        $sqlStateMatched = in_array($sqlState, ['23000', '23505', '19'], true);
        $driverCodeMatched = in_array($driverCode, ['1062', '1555', '2067', '2601', '2627'], true);
        $duplicateKeywordMatched = str_contains($message, 'duplicate')
            || str_contains($message, 'unique constraint')
            || str_contains($message, 'integrity constraint violation')
            || str_contains($message, 'unique violation');

        return ($sqlStateMatched || $driverCodeMatched) && ($constraintMatched || $duplicateKeywordMatched);
    }

    public function findById(int $id): ?ReservationBooking
    {
        return ReservationBooking::query()->find($id);
    }

    public function findDetailForAdmin(int $bookingId): ?ReservationBooking
    {
        return ReservationBooking::query()
            ->with(['service', 'schedule', 'logs'])
            ->find($bookingId);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateStatus(int $bookingId, string $status, array $attributes = []): ?ReservationBooking
    {
        $booking = ReservationBooking::query()->find($bookingId);

        if ($booking === null) {
            return null;
        }

        $booking->fill(array_merge($attributes, ['status' => $status]));
        $booking->save();

        return $booking->fresh(['service', 'schedule', 'logs']);
    }

    public function findByIdForUpdate(int $bookingId): ?ReservationBooking
    {
        return ReservationBooking::query()
            ->with(['service', 'schedule', 'logs'])
            ->lockForUpdate()
            ->find($bookingId);
    }

    public function findByIdAndPhoneForUpdate(int $bookingId, string $customerPhone): ?ReservationBooking
    {
        return ReservationBooking::query()
            ->with(['service', 'schedule', 'logs'])
            ->whereKey($bookingId)
            ->where('customer_phone', $customerPhone)
            ->lockForUpdate()
            ->first();
    }

    public function findDetailedById(int $id): ?ReservationBooking
    {
        return $this->findDetailForAdmin($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateById(int $id, array $data): ?ReservationBooking
    {
        $booking = ReservationBooking::query()->find($id);

        if ($booking === null) {
            return null;
        }

        $booking->fill($data);
        $booking->save();

        return $booking->fresh(['service', 'schedule', 'logs']);
    }

    /**
     * @return Collection<int, ReservationBooking>
     */
    public function findByPhone(string $phone): Collection
    {
        return ReservationBooking::query()
            ->where('customer_phone', $phone)
            ->orderByDesc('booking_date')
            ->orderByDesc('booking_time')
            ->get();
    }

    /**
     * @return Collection<int, ReservationBooking>
     */
    public function findLookupBookingsByPhone(string $phone): Collection
    {
        return ReservationBooking::query()
            ->with('service')
            ->where('customer_phone', $phone)
            ->whereIn('status', [
                BookingStatus::Pending->value,
                BookingStatus::Confirmed->value,
                BookingStatus::Cancelled->value,
            ])
            ->orderByDesc('booking_date')
            ->orderByDesc('booking_time')
            ->orderByDesc('created_at')
            ->get();
    }
}
