<?php

namespace Modules\Glitter\Reservation\Tests\Unit;

require_once __DIR__.'/../ModuleTestCase.php';

use Illuminate\Support\Str;
use Modules\Glitter\Reservation\Models\ReservationSchedule;
use Modules\Glitter\Reservation\Models\ReservationService;
use Modules\Glitter\Reservation\Services\HolidayService;
use Modules\Glitter\Reservation\Services\SlotService;
use Modules\Glitter\Reservation\Tests\ModuleTestCase;
use RuntimeException;

class HolidayManagementTest extends ModuleTestCase
{
    public function test_it_creates_a_common_holiday_successfully(): void
    {
        $holidayService = $this->app->make(HolidayService::class);

        $holiday = $holidayService->createCommonHoliday([
            'holiday_date' => now()->addDays(20)->toDateString(),
            'name' => '개원기념일',
            'is_recurring_yearly' => false,
            'notes' => '휴무 테스트',
        ], null);

        $this->assertDatabaseHas('reservation_holidays', [
            'id' => $holiday->id,
            'reservation_service_id' => null,
            'name' => '개원기념일',
        ]);
    }

    public function test_it_blocks_duplicate_common_holiday_on_the_same_date(): void
    {
        $holidayService = $this->app->make(HolidayService::class);
        $holidayDate = now()->addDays(21)->toDateString();

        $holidayService->createCommonHoliday([
            'holiday_date' => $holidayDate,
            'name' => '첫 휴무',
        ], null);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('같은 날짜의 휴무일이 이미 등록되어 있습니다.');

        $holidayService->createCommonHoliday([
            'holiday_date' => $holidayDate,
            'name' => '중복 휴무',
        ], null);
    }

    public function test_it_returns_no_slots_after_adding_a_holiday_for_the_date(): void
    {
        $service = $this->createActiveService();
        $targetDate = now()->addDays(22);

        $this->createSchedule($service->id, $targetDate->dayOfWeek);

        $holidayService = $this->app->make(HolidayService::class);
        $holidayService->createCommonHoliday([
            'holiday_date' => $targetDate->toDateString(),
            'name' => '임시 휴무',
        ], null);

        $slotService = $this->app->make(SlotService::class);
        $slots = $slotService->getAvailableSlots($service->id, $targetDate->toDateString());

        $this->assertSame([], $slots);
    }

    private function createActiveService(): ReservationService
    {
        return ReservationService::query()->create([
            'name' => '상담 서비스',
            'slug' => 'holiday-service-'.Str::lower(Str::random(6)),
            'duration_minutes' => 60,
            'slot_interval_minutes' => 30,
            'capacity' => 1,
            'price' => 0,
            'currency' => 'KRW',
            'min_booking_days' => 0,
            'max_booking_days' => 90,
            'is_active' => true,
            'sort_order' => 0,
        ]);
    }

    private function createSchedule(int $serviceId, int $dayOfWeek): ReservationSchedule
    {
        return ReservationSchedule::query()->create([
            'reservation_service_id' => $serviceId,
            'day_of_week' => $dayOfWeek,
            'start_time' => '10:00:00',
            'end_time' => '13:00:00',
            'slot_capacity' => 1,
            'is_active' => true,
        ]);
    }
}
