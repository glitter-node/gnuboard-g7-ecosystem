<?php

namespace Modules\Glitter\Reservation\Services;

use Carbon\Carbon;
use DateTimeInterface;
use Modules\Glitter\Reservation\Repositories\BookingRepository;
use Modules\Glitter\Reservation\Repositories\HolidayRepository;
use Modules\Glitter\Reservation\Repositories\ScheduleRepository;
use Modules\Glitter\Reservation\Repositories\ServiceRepository;

class SlotService
{
    public function __construct(
        private ServiceRepository $serviceRepository,
        private ScheduleRepository $scheduleRepository,
        private HolidayRepository $holidayRepository,
        private BookingRepository $bookingRepository,
    ) {}

    /**
     * @return array<int, string>
     */
    public function getAvailableSlots(int $serviceId, string $bookingDate): array
    {
        $service = $this->serviceRepository->findActiveById($serviceId);

        if ($service === null || $this->holidayRepository->isHoliday($bookingDate)) {
            return [];
        }

        $date = Carbon::parse($bookingDate);
        $dayOfWeek = $date->dayOfWeek;
        $schedules = $this->scheduleRepository->getActiveSchedulesForDayOfWeek($dayOfWeek, $serviceId);

        if ($schedules->isEmpty()) {
            return [];
        }

        $slots = [];
        $intervalMinutes = max((int) $service->slot_interval_minutes, 30);
        $durationMinutes = max((int) $service->duration_minutes, 1);

        foreach ($schedules as $schedule) {
            $scheduleSlots = $this->buildSlotsFromSchedule(
                bookingDate: $date,
                startTime: $schedule->getAttribute('start_time'),
                endTime: $schedule->getAttribute('end_time'),
                breakStartTime: $schedule->getAttribute('break_start_time'),
                breakEndTime: $schedule->getAttribute('break_end_time'),
                intervalMinutes: $intervalMinutes,
                durationMinutes: $durationMinutes,
            );

            foreach ($scheduleSlots as $slot) {
                if (! $this->isPastSlot($date, $slot) && ! $this->isBookedSlot($serviceId, $bookingDate, $slot)) {
                    $slots[$slot] = $slot;
                }
            }
        }

        ksort($slots);

        return array_values($slots);
    }

    /**
     * @return array<int, string>
     */
    private function buildSlotsFromSchedule(
        Carbon $bookingDate,
        mixed $startTime,
        mixed $endTime,
        mixed $breakStartTime,
        mixed $breakEndTime,
        int $intervalMinutes,
        int $durationMinutes,
    ): array {
        $scheduleStart = $this->makeDateTime($bookingDate, $startTime);
        $scheduleEnd = $this->makeDateTime($bookingDate, $endTime);

        if ($scheduleEnd->lessThanOrEqualTo($scheduleStart)) {
            return [];
        }

        $breakStart = $this->makeOptionalDateTime($bookingDate, $breakStartTime);
        $breakEnd = $this->makeOptionalDateTime($bookingDate, $breakEndTime);
        $slots = [];
        $current = $scheduleStart->copy();

        while ($current->copy()->addMinutes($durationMinutes)->lessThanOrEqualTo($scheduleEnd)) {
            $slotEnd = $current->copy()->addMinutes($durationMinutes);

            if (! $this->overlapsBreak($current, $slotEnd, $breakStart, $breakEnd)) {
                $slots[] = $current->format('H:i');
            }

            $current->addMinutes($intervalMinutes);
        }

        return $slots;
    }

    private function overlapsBreak(
        Carbon $slotStart,
        Carbon $slotEnd,
        ?Carbon $breakStart,
        ?Carbon $breakEnd,
    ): bool {
        if ($breakStart === null || $breakEnd === null || $breakEnd->lessThanOrEqualTo($breakStart)) {
            return false;
        }

        return $slotStart->lt($breakEnd) && $slotEnd->gt($breakStart);
    }

    private function isPastSlot(Carbon $bookingDate, string $slot): bool
    {
        if (! $bookingDate->isToday()) {
            return false;
        }

        return $this->makeDateTime($bookingDate, $slot)->lessThanOrEqualTo(now());
    }

    private function isBookedSlot(int $serviceId, string $bookingDate, string $slot): bool
    {
        return $this->bookingRepository->existsConfirmedOrPendingAt($serviceId, $bookingDate, $slot);
    }

    private function makeDateTime(Carbon $bookingDate, mixed $time): Carbon
    {
        return Carbon::parse($bookingDate->format('Y-m-d').' '.$this->normalizeTime($time));
    }

    private function makeOptionalDateTime(Carbon $bookingDate, mixed $time): ?Carbon
    {
        if ($time === null || $time === '') {
            return null;
        }

        return $this->makeDateTime($bookingDate, $time);
    }

    private function normalizeTime(mixed $time): string
    {
        if ($time instanceof DateTimeInterface) {
            return $time->format('H:i:s');
        }

        $timeString = trim((string) $time);

        if ($timeString === '') {
            return $timeString;
        }

        if (preg_match('/\d{2}:\d{2}:\d{2}$/', $timeString) === 1) {
            return substr($timeString, -8);
        }

        return strlen($timeString) === 5 ? $timeString.':00' : $timeString;
    }
}
