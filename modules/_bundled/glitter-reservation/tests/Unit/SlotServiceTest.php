<?php

namespace Modules\Glitter\Reservation\Tests\Unit;

require_once __DIR__.'/../ModuleTestCase.php';

use Illuminate\Support\Str;
use Modules\Glitter\Reservation\Enums\BookingStatus;
use Modules\Glitter\Reservation\Models\ReservationBooking;
use Modules\Glitter\Reservation\Models\ReservationHoliday;
use Modules\Glitter\Reservation\Models\ReservationSchedule;
use Modules\Glitter\Reservation\Models\ReservationService;
use Modules\Glitter\Reservation\Services\SlotService;
use Modules\Glitter\Reservation\Tests\ModuleTestCase;

class SlotServiceTest extends ModuleTestCase
{
    public function test_it_returns_no_slots_when_the_booking_date_is_a_holiday(): void
    {
        $service = $this->createActiveService();
        $targetDate = now()->addDays(4);

        $this->createSchedule($service->id, $targetDate->dayOfWeek);

        ReservationHoliday::query()->create([
            'holiday_date' => $targetDate->toDateString(),
            'name' => '임시 휴무',
        ]);

        $slotService = $this->app->make(SlotService::class);
        $slots = $slotService->getAvailableSlots($service->id, $targetDate->toDateString());

        $this->assertSame([], $slots);
    }

    public function test_it_excludes_a_time_slot_when_a_pending_booking_already_exists(): void
    {
        $service = $this->createActiveService();
        $targetDate = now()->addDays(5);

        $this->createSchedule($service->id, $targetDate->dayOfWeek);

        ReservationBooking::query()->create([
            'reservation_service_id' => $service->id,
            'booking_code' => 'RSV-'.Str::upper(Str::random(8)),
            'customer_name' => '기존 예약자',
            'customer_phone' => '01055556666',
            'booking_date' => $targetDate->toDateString(),
            'booking_time' => '10:30:00',
            'booking_end_time' => '11:30:00',
            'guest_count' => 1,
            'status' => BookingStatus::Pending->value,
        ]);

        $slotService = $this->app->make(SlotService::class);
        $slots = $slotService->getAvailableSlots($service->id, $targetDate->toDateString());

        $this->assertContains('10:00', $slots);
        $this->assertNotContains('10:30', $slots);
        $this->assertContains('11:00', $slots);
    }

    private function createActiveService(): ReservationService
    {
        return ReservationService::query()->create([
            'name' => '입학 상담',
            'slug' => 'slot-service-'.Str::lower(Str::random(6)),
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
