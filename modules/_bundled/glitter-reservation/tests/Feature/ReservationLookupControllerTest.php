<?php

namespace Modules\Glitter\Reservation\Tests\Feature;

require_once __DIR__.'/../ModuleTestCase.php';

use Illuminate\Support\Facades\Route;
use Modules\Glitter\Reservation\Http\Controllers\ReservationController;
use Modules\Glitter\Reservation\Enums\BookingStatus;
use Modules\Glitter\Reservation\Models\ReservationBooking;
use Modules\Glitter\Reservation\Models\ReservationSchedule;
use Modules\Glitter\Reservation\Models\ReservationService;
use Modules\Glitter\Reservation\Tests\ModuleTestCase;

class ReservationLookupControllerTest extends ModuleTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! app('router')->getRoutes()->getByName('reservation.testing.bookings.lookup')) {
            Route::middleware('api')
                ->get('/api/_testing/reservation/bookings/lookup', [ReservationController::class, 'lookupBookings'])
                ->name('reservation.testing.bookings.lookup');
        }
    }

    public function test_lookup_returns_bookings_for_customer_phone(): void
    {
        $service = $this->createService();
        $schedule = $this->createSchedule($service->id, now()->addDay()->dayOfWeek);
        $lookupPhone = '010'.random_int(10000000, 99999999);

        $confirmedBooking = $this->createBooking(
            $service->id,
            $schedule->id,
            $lookupPhone,
            BookingStatus::Confirmed,
            now()->addDay()->toDateString(),
            '10:00:00',
        );
        $cancelledBooking = $this->createBooking(
            $service->id,
            $schedule->id,
            $lookupPhone,
            BookingStatus::Cancelled,
            now()->addDays(2)->toDateString(),
            '11:00:00',
        );
        $this->createBooking(
            $service->id,
            $schedule->id,
            '01099990000',
            BookingStatus::Pending,
            now()->addDays(3)->toDateString(),
            '12:00:00',
        );

        $response = $this->getJson('/api/_testing/reservation/bookings/lookup?customer_phone='.$lookupPhone);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $items = $response->json('data');
        $itemIds = collect($items)->pluck('id');
        $itemStatuses = collect($items)->pluck('status');

        $this->assertIsArray($items);
        $this->assertTrue($itemIds->contains($confirmedBooking->id));
        $this->assertTrue($itemIds->contains($cancelledBooking->id));
        $this->assertTrue($itemStatuses->contains(BookingStatus::Confirmed->value));
        $this->assertTrue($itemStatuses->contains(BookingStatus::Cancelled->value));
        $this->assertSame($service->id, $items[0]['service']['id']);
        $this->assertArrayHasKey('booking_date', $items[0]);
        $this->assertArrayHasKey('booking_time', $items[0]);
        $this->assertArrayHasKey('status', $items[0]);
        $this->assertArrayHasKey('created_at', $items[0]);
    }

    public function test_lookup_excludes_completed_and_no_show_bookings(): void
    {
        $service = $this->createService();
        $schedule = $this->createSchedule($service->id, now()->addDay()->dayOfWeek);
        $lookupPhone = '010'.random_int(10000000, 99999999);

        $this->createBooking(
            $service->id,
            $schedule->id,
            $lookupPhone,
            BookingStatus::Completed,
            now()->addDay()->toDateString(),
            '10:00:00',
        );
        $this->createBooking(
            $service->id,
            $schedule->id,
            $lookupPhone,
            BookingStatus::NoShow,
            now()->addDays(2)->toDateString(),
            '11:00:00',
        );
        $pendingBooking = $this->createBooking(
            $service->id,
            $schedule->id,
            $lookupPhone,
            BookingStatus::Pending,
            now()->addDays(3)->toDateString(),
            '12:00:00',
        );

        $response = $this->getJson('/api/_testing/reservation/bookings/lookup?customer_phone='.$lookupPhone);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $items = $response->json('data');

        $this->assertIsArray($items);
        $this->assertCount(1, $items);
        $this->assertSame($pendingBooking->id, $items[0]['id']);
        $this->assertSame(BookingStatus::Pending->value, $items[0]['status']);
    }

    public function test_lookup_requires_customer_phone(): void
    {
        $response = $this->getJson('/api/_testing/reservation/bookings/lookup');

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['success', 'message', 'errors']);
    }

    public function test_lookup_returns_empty_list_for_unknown_phone(): void
    {
        $response = $this->getJson('/api/_testing/reservation/bookings/lookup?customer_phone=01000009999');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data', []);
    }

    private function createService(): ReservationService
    {
        return ReservationService::query()->create([
            'name' => '예약 조회 테스트 상담',
            'slug' => 'lookup-service-'.substr(md5((string) microtime(true)), 0, 8),
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
            'end_time' => '18:00:00',
            'is_active' => true,
        ]);
    }

    private function createBooking(
        int $serviceId,
        int $scheduleId,
        string $customerPhone,
        BookingStatus $status,
        string $bookingDate,
        string $bookingTime,
    ): ReservationBooking {
        return ReservationBooking::query()->create([
            'reservation_service_id' => $serviceId,
            'reservation_schedule_id' => $scheduleId,
            'booking_code' => 'LKP-'.substr(md5(uniqid('', true)), 0, 10),
            'customer_name' => '조회 테스트',
            'customer_phone' => $customerPhone,
            'booking_date' => $bookingDate,
            'booking_time' => $bookingTime,
            'booking_end_time' => '13:00:00',
            'status' => $status->value,
            'confirmed_at' => $status === BookingStatus::Confirmed ? now() : null,
            'cancelled_at' => $status === BookingStatus::Cancelled ? now() : null,
            'completed_at' => $status === BookingStatus::Completed ? now() : null,
        ]);
    }
}
