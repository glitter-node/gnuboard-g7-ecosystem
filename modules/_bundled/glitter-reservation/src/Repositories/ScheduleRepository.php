<?php

namespace Modules\Glitter\Reservation\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Glitter\Reservation\Models\ReservationSchedule;

class ScheduleRepository
{
    /**
     * @return Collection<int, ReservationSchedule>
     */
    public function getActiveSchedulesForDayOfWeek(int $dayOfWeek, ?int $serviceId = null): Collection
    {
        return ReservationSchedule::query()
            ->where('is_active', true)
            ->where('day_of_week', $dayOfWeek)
            ->when($serviceId !== null, function ($query) use ($serviceId) {
                $query->where('reservation_service_id', $serviceId);
            })
            ->orderBy('start_time')
            ->get();
    }
}
