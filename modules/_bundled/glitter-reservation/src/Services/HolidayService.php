<?php

namespace Modules\Glitter\Reservation\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Modules\Glitter\Reservation\Models\ReservationHoliday;
use Modules\Glitter\Reservation\Repositories\HolidayRepository;
use RuntimeException;

class HolidayService
{
    public function __construct(private HolidayRepository $holidayRepository)
    {
    }

    /**
     * 현재 단계에서는 공통 휴무만 지원합니다.
     * service별 휴무는 reservation_service_id 범위를 확장할 때 별도 구현합니다.
     *
     * @return Collection<int, ReservationHoliday>
     */
    public function getCommonHolidays(): Collection
    {
        return $this->holidayRepository->getCommonHolidays();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function createCommonHoliday(array $payload, ?int $actorUserId = null): ReservationHoliday
    {
        $holidayDate = (string) $payload['holiday_date'];

        if ($this->holidayRepository->existsCommonHolidayByDate($holidayDate)) {
            throw new RuntimeException('같은 날짜의 휴무일이 이미 등록되어 있습니다.');
        }

        return DB::transaction(function () use ($payload, $actorUserId): ReservationHoliday {
            return $this->holidayRepository->create([
                'reservation_service_id' => null,
                'holiday_date' => $payload['holiday_date'],
                'name' => $payload['name'],
                'is_recurring_yearly' => (bool) ($payload['is_recurring_yearly'] ?? false),
                'notes' => $payload['notes'] ?? null,
                'created_by' => $actorUserId,
                'updated_by' => $actorUserId,
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateCommonHoliday(int $holidayId, array $payload, ?int $actorUserId = null): ReservationHoliday
    {
        $holiday = $this->holidayRepository->findCommonById($holidayId);

        if ($holiday === null) {
            throw new ModelNotFoundException('휴무일을 찾을 수 없습니다.');
        }

        $holidayDate = (string) $payload['holiday_date'];

        if ($this->holidayRepository->existsCommonHolidayByDate($holidayDate, $holidayId)) {
            throw new RuntimeException('같은 날짜의 휴무일이 이미 등록되어 있습니다.');
        }

        return DB::transaction(function () use ($holidayId, $payload, $actorUserId): ReservationHoliday {
            $updatedHoliday = $this->holidayRepository->update($holidayId, [
                'holiday_date' => $payload['holiday_date'],
                'name' => $payload['name'],
                'is_recurring_yearly' => (bool) ($payload['is_recurring_yearly'] ?? false),
                'notes' => $payload['notes'] ?? null,
                'updated_by' => $actorUserId,
            ]);

            if ($updatedHoliday === null) {
                throw new ModelNotFoundException('휴무일을 찾을 수 없습니다.');
            }

            return $updatedHoliday;
        });
    }

    public function deleteCommonHoliday(int $holidayId): void
    {
        $deleted = $this->holidayRepository->delete($holidayId);

        if (! $deleted) {
            throw new ModelNotFoundException('휴무일을 찾을 수 없습니다.');
        }
    }
}
