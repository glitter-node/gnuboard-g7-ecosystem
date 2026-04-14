<?php

namespace Modules\Glitter\Reservation\Tests\Feature;

require_once __DIR__.'/../ModuleTestCase.php';

use Illuminate\Support\Facades\Route;
use Modules\Glitter\Reservation\Enums\BookingStatus;
use Modules\Glitter\Reservation\Http\Controllers\ReservationController;
use Modules\Glitter\Reservation\Models\ReservationBooking;
use Modules\Glitter\Reservation\Models\ReservationSchedule;
use Modules\Glitter\Reservation\Models\ReservationService;
use Modules\Glitter\Reservation\Services\SlotService;
use Modules\Glitter\Reservation\Tests\ModuleTestCase;

class ReservationCancelControllerTest extends ModuleTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! app('router')->getRoutes()->getByName('reservation.testing.bookings.cancel')) {
            Route::middleware('api')
                ->post('/api/_testing/reservation/bookings/{booking}/cancel', [ReservationController::class, 'cancelBooking'])
                ->name('reservation.testing.bookings.cancel');
        }
    }

    public function test_customer_can_cancel_pending_booking(): void
    {
        $service = $this->createService();
        $schedule = $this->createSchedule($service->id, now()->addDay()->dayOfWeek);
        $phone = '010'.random_int(10000000, 99999999);
        $booking = $this->createBooking($service->id, $schedule->id, $phone, BookingStatus::Pending);

        $response = $this->postJson("/api/_testing/reservation/bookings/{$booking->id}/cancel", [
            'customer_phone' => $phone,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $booking->id)
            ->assertJsonPath('data.status', BookingStatus::Cancelled->value);

        $this->assertDatabaseHas('reservation_bookings', [
            'id' => $booking->id,
            'status' => BookingStatus::Cancelled->value,
        ]);

        $this->assertDatabaseHas('reservation_booking_logs', [
            'reservation_booking_id' => $booking->id,
            'from_status' => BookingStatus::Pending->value,
            'to_status' => BookingStatus::Cancelled->value,
            'description' => 'customer_cancelled',
        ]);
    }

    public function test_customer_can_cancel_confirmed_booking(): void
    {
        $service = $this->createService();
        $schedule = $this->createSchedule($service->id, now()->addDay()->dayOfWeek);
        $phone = '010'.random_int(10000000, 99999999);
        $booking = $this->createBooking($service->id, $schedule->id, $phone, BookingStatus::Confirmed);

        $response = $this->postJson("/api/_testing/reservation/bookings/{$booking->id}/cancel", [
            'customer_phone' => $phone,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', BookingStatus::Cancelled->value);
    }

    public function test_customer_cannot_cancel_completed_booking(): void
    {
        $service = $this->createService();
        $schedule = $this->createSchedule($service->id, now()->addDay()->dayOfWeek);
        $phone = '010'.random_int(10000000, 99999999);
        $booking = $this->createBooking($service->id, $schedule->id, $phone, BookingStatus::Completed);

        $response = $this->postJson("/api/_testing/reservation/bookings/{$booking->id}/cancel", [
            'customer_phone' => $phone,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', '완료된 예약은 고객이 취소할 수 없습니다.');
    }

    public function test_customer_cannot_cancel_already_cancelled_booking(): void
    {
        $service = $this->createService();
        $schedule = $this->createSchedule($service->id, now()->addDay()->dayOfWeek);
        $phone = '010'.random_int(10000000, 99999999);
        $booking = $this->createBooking($service->id, $schedule->id, $phone, BookingStatus::Cancelled);

        $response = $this->postJson("/api/_testing/reservation/bookings/{$booking->id}/cancel", [
            'customer_phone' => $phone,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', '이미 취소된 예약입니다.');
    }

    public function test_customer_cannot_cancel_no_show_booking(): void
    {
        $service = $this->createService();
        $schedule = $this->createSchedule($service->id, now()->addDay()->dayOfWeek);
        $phone = '010'.random_int(10000000, 99999999);
        $booking = $this->createBooking($service->id, $schedule->id, $phone, BookingStatus::NoShow);

        $response = $this->postJson("/api/_testing/reservation/bookings/{$booking->id}/cancel", [
            'customer_phone' => $phone,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', '노쇼 처리된 예약은 고객이 취소할 수 없습니다.');
    }

    public function test_customer_cannot_cancel_other_customer_booking(): void
    {
        $service = $this->createService();
        $schedule = $this->createSchedule($service->id, now()->addDay()->dayOfWeek);
        $booking = $this->createBooking($service->id, $schedule->id, '01011112222', BookingStatus::Pending);

        $response = $this->postJson("/api/_testing/reservation/bookings/{$booking->id}/cancel", [
            'customer_phone' => '01099998888',
        ]);

        $response->assertNotFound()
            ->assertJsonPath('success', false);
    }

    public function test_cancel_requires_customer_phone(): void
    {
        $response = $this->postJson('/api/_testing/reservation/bookings/999/cancel', []);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['success', 'message', 'errors']);
    }

    public function test_cancelled_booking_slot_is_available_again_after_customer_cancellation(): void
    {
        $service = $this->createService();
        $targetDate = now()->addDay();
        $schedule = $this->createSchedule($service->id, $targetDate->dayOfWeek);
        $phone = '010'.random_int(10000000, 99999999);
        $booking = $this->createBooking($service->id, $schedule->id, $phone, BookingStatus::Pending);

        $response = $this->postJson("/api/_testing/reservation/bookings/{$booking->id}/cancel", [
            'customer_phone' => $phone,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', BookingStatus::Cancelled->value);

        $slots = $this->app->make(SlotService::class)->getAvailableSlots($service->id, $targetDate->toDateString());

        $this->assertContains('10:00', $slots);
    }

    private function createService(): ReservationService
    {
        return ReservationService::query()->create([
            'name' => '예약 취소 테스트 상담',
            'slug' => 'cancel-service-'.substr(md5((string) microtime(true)), 0, 8),
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
    ): ReservationBooking {
        return ReservationBooking::query()->create([
            'reservation_service_id' => $serviceId,
            'reservation_schedule_id' => $scheduleId,
            'booking_code' => 'CNL-'.substr(md5(uniqid('', true)), 0, 10),
            'customer_name' => '취소 테스트',
            'customer_phone' => $customerPhone,
            'booking_date' => now()->addDay()->toDateString(),
            'booking_time' => '10:00:00',
            'booking_end_time' => '11:00:00',
            'status' => $status->value,
            'confirmed_at' => $status === BookingStatus::Confirmed ? now() : null,
            'cancelled_at' => $status === BookingStatus::Cancelled ? now() : null,
            'completed_at' => $status === BookingStatus::Completed ? now() : null,
        ]);
    }
}
