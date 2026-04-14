<?php

namespace Modules\Glitter\Reservation\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Glitter\Reservation\Models\ReservationHoliday;

class HolidayRepository
{
    public function isHoliday(string $date): bool
    {
        return ReservationHoliday::query()
            ->whereDate('holiday_date', $date)
            ->exists();
    }

    /**
     * @return Collection<int, ReservationHoliday>
     */
    public function getCommonHolidays(): Collection
    {
        return ReservationHoliday::query()
            ->whereNull('reservation_service_id')
            ->orderBy('holiday_date')
            ->orderBy('id')
            ->get();
    }

    public function existsCommonHolidayByDate(string $holidayDate, ?int $ignoreId = null): bool
    {
        $query = ReservationHoliday::query()
            ->whereNull('reservation_service_id')
            ->whereDate('holiday_date', $holidayDate);

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        return $query->exists();
    }

    public function findCommonById(int $holidayId): ?ReservationHoliday
    {
        return ReservationHoliday::query()
            ->whereNull('reservation_service_id')
            ->find($holidayId);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ReservationHoliday
    {
        return ReservationHoliday::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $holidayId, array $data): ?ReservationHoliday
    {
        $holiday = $this->findCommonById($holidayId);

        if ($holiday === null) {
            return null;
        }

        $holiday->fill($data);
        $holiday->save();

        return $holiday->fresh();
    }

    public function delete(int $holidayId): bool
    {
        $holiday = $this->findCommonById($holidayId);

        if ($holiday === null) {
            return false;
        }

        return (bool) $holiday->delete();
    }
}
