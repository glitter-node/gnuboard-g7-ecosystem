<?php

namespace Modules\Glitter\Reservation\Tests\Feature;

require_once __DIR__.'/../ModuleTestCase.php';

use App\Models\User;
use Carbon\Carbon;
use Modules\Glitter\Reservation\Enums\BookingStatus;
use Modules\Glitter\Reservation\Models\ReservationBooking;
use Modules\Glitter\Reservation\Models\ReservationSchedule;
use Modules\Glitter\Reservation\Models\ReservationService;
use Modules\Glitter\Reservation\Tests\ModuleTestCase;

class AdminBookingControllerTest extends ModuleTestCase
{
    protected User $adminUser;

    protected ReservationService $service;

    protected ReservationSchedule $schedule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = $this->createAdminUser([
            'reservation.reservations.read',
            'reservation.reservations.update',
        ]);

        $this->service = $this->createService();
        $this->schedule = $this->createSchedule($this->service->id, now()->addDay()->dayOfWeek);
    }

    public function test_admin_can_list_bookings_with_status_filter(): void
    {
        $pendingBooking = $this->createBooking(status: BookingStatus::Pending);
        $cancelledBooking = $this->createBooking(status: BookingStatus::Cancelled, time: '11:00:00', phone: '01099998888');

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/modules/glitter-reservation/admin/reservation/bookings?status=pending');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $items = $response->json('data.data') ?? $response->json('data');
        $itemIds = collect($items)->pluck('id');
        $itemStatuses = collect($items)->pluck('status');

        $this->assertIsArray($items);
        $this->assertTrue($itemIds->contains($pendingBooking->id));
        $this->assertFalse($itemIds->contains($cancelledBooking->id));
        $this->assertTrue($itemStatuses->every(static fn (mixed $status): bool => $status === BookingStatus::Pending->value));
    }

    public function test_admin_can_show_existing_booking_detail(): void
    {
        $booking = $this->createBooking();

        $response = $this->actingAs($this->adminUser)
            ->getJson("/api/modules/glitter-reservation/admin/reservation/bookings/{$booking->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $booking->id)
            ->assertJsonPath('data.service.id', $this->service->id)
            ->assertJsonPath('data.status', BookingStatus::Pending->value)
            ->assertJsonPath('data.customer_email', 'admin@example.com')
            ->assertJsonPath('data.student_grade', '중등 1학년');
    }

    public function test_admin_show_returns_not_found_response_for_missing_booking(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/modules/glitter-reservation/admin/reservation/bookings/999999');

        $response->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['success', 'message']);
    }

    public function test_admin_can_change_booking_status_from_pending_to_confirmed(): void
    {
        $booking = $this->createBooking(status: BookingStatus::Pending);

        $response = $this->actingAs($this->adminUser)
            ->patchJson("/api/modules/glitter-reservation/admin/reservation/bookings/{$booking->id}/status", [
                'status' => BookingStatus::Confirmed->value,
                'admin_memo' => '관리자 확정',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $booking->id)
            ->assertJsonPath('data.status', BookingStatus::Confirmed->value);

        $this->assertDatabaseHas('reservation_bookings', [
            'id' => $booking->id,
            'status' => BookingStatus::Confirmed->value,
        ]);
    }

    public function test_admin_cannot_change_cancelled_booking_to_confirmed(): void
    {
        $booking = $this->createBooking(status: BookingStatus::Cancelled);

        $response = $this->actingAs($this->adminUser)
            ->patchJson("/api/modules/glitter-reservation/admin/reservation/bookings/{$booking->id}/status", [
                'status' => BookingStatus::Confirmed->value,
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['success', 'message']);
    }

    public function test_admin_status_update_response_keeps_project_response_format_and_creates_log(): void
    {
        $booking = $this->createBooking(status: BookingStatus::Pending);

        $response = $this->actingAs($this->adminUser)
            ->patchJson("/api/modules/glitter-reservation/admin/reservation/bookings/{$booking->id}/status", [
                'status' => BookingStatus::Confirmed->value,
                'admin_memo' => '응답 형식 점검',
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'status',
                    'booking_logs',
                ],
            ]);
        $this->assertIsArray($response->json('data.booking_logs'));

        $this->assertDatabaseHas('reservation_booking_logs', [
            'reservation_booking_id' => $booking->id,
            'from_status' => BookingStatus::Pending->value,
            'to_status' => BookingStatus::Confirmed->value,
            'event_type' => 'status_changed',
        ]);
    }

    protected function createService(): ReservationService
    {
        return ReservationService::query()->create([
            'name' => '관리자 테스트 상담',
            'slug' => 'admin-booking-'.substr(md5((string) microtime(true)), 0, 8),
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

    protected function createSchedule(int $serviceId, int $dayOfWeek): ReservationSchedule
    {
        return ReservationSchedule::query()->create([
            'reservation_service_id' => $serviceId,
            'day_of_week' => $dayOfWeek,
            'start_time' => '10:00:00',
            'end_time' => '18:00:00',
            'is_active' => true,
        ]);
    }

    protected function createBooking(
        BookingStatus $status = BookingStatus::Pending,
        ?string $date = null,
        string $time = '10:00:00',
        string $phone = '01012345678'
    ): ReservationBooking {
        $bookingDate = Carbon::parse($date ?? now()->addDay()->toDateString());

        $booking = ReservationBooking::query()->create([
            'reservation_service_id' => $this->service->id,
            'reservation_schedule_id' => $this->schedule->id,
            'booking_code' => 'ADM-'.substr(md5(uniqid('', true)), 0, 10),
            'booking_date' => $bookingDate->toDateString(),
            'booking_time' => $time,
            'booking_end_time' => Carbon::createFromFormat('H:i:s', $time)->addMinutes(60)->format('H:i:s'),
            'customer_name' => '관리자 테스트',
            'customer_phone' => $phone,
            'customer_email' => 'admin@example.com',
            'student_grade' => '중등 1학년',
            'status' => $status->value,
            'request_memo' => '관리자 테스트 예약',
            'confirmed_at' => $status === BookingStatus::Confirmed ? now() : null,
            'cancelled_at' => $status === BookingStatus::Cancelled ? now() : null,
        ]);

        $booking->logs()->create([
            'event_type' => 'customer_created',
            'from_status' => null,
            'to_status' => BookingStatus::Pending->value,
            'description' => 'customer_created',
            'payload' => ['student_grade' => '중등 1학년'],
            'logged_by' => null,
        ]);

        return $booking->fresh(['service', 'logs']);
    }
}
