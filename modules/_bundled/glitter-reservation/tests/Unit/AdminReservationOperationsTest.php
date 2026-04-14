<?php

namespace Modules\Glitter\Reservation\Tests\Unit;

require_once __DIR__.'/../ModuleTestCase.php';

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use Modules\Glitter\Reservation\Enums\BookingStatus;
use Modules\Glitter\Reservation\Models\ReservationBooking;
use Modules\Glitter\Reservation\Models\ReservationBookingLog;
use Modules\Glitter\Reservation\Models\ReservationSchedule;
use Modules\Glitter\Reservation\Models\ReservationService;
use Modules\Glitter\Reservation\Repositories\BookingRepository;
use Modules\Glitter\Reservation\Services\ReservationService as ReservationDomainService;
use Modules\Glitter\Reservation\Tests\ModuleTestCase;
use RuntimeException;

class AdminReservationOperationsTest extends ModuleTestCase
{
    public function test_it_filters_admin_booking_list_by_status(): void
    {
        $service = $this->createActiveService();
        $targetDate = now()->addDays(7)->toDateString();

        $pendingBooking = $this->createBooking($service->id, $targetDate, '10:00:00', BookingStatus::Pending);
        $cancelledBooking = $this->createBooking($service->id, $targetDate, '11:00:00', BookingStatus::Cancelled);

        $repository = $this->app->make(BookingRepository::class);
        $result = $repository->paginateForAdmin(['status' => BookingStatus::Pending->value], 15);
        $pendingIds = collect($result->items())->map(static fn (ReservationBooking $booking): int => (int) $booking->id);
        $statuses = collect($result->items())->map(static fn (ReservationBooking $booking): string => $booking->status->value);

        $this->assertTrue($pendingIds->contains($pendingBooking->id));
        $this->assertFalse($pendingIds->contains($cancelledBooking->id));
        $this->assertTrue($statuses->every(static fn (string $status): bool => $status === BookingStatus::Pending->value));
    }

    public function test_it_returns_admin_booking_detail_successfully(): void
    {
        $service = $this->createActiveService();
        $booking = $this->createBooking($service->id, now()->addDays(8)->toDateString(), '10:00:00', BookingStatus::Pending);

        ReservationBookingLog::query()->create([
            'reservation_booking_id' => $booking->id,
            'event_type' => 'status_changed',
            'from_status' => BookingStatus::Pending->value,
            'to_status' => BookingStatus::Confirmed->value,
            'description' => '관리자 확정',
            'payload' => null,
            'logged_by' => null,
        ]);

        $serviceLayer = $this->app->make(ReservationDomainService::class);
        $detail = $serviceLayer->getAdminBooking($booking->id);

        $this->assertSame($booking->id, $detail->id);
        $this->assertTrue($detail->relationLoaded('service'));
        $this->assertTrue($detail->relationLoaded('logs'));
        $this->assertCount(1, $detail->logs);
    }

    public function test_it_changes_booking_status_from_pending_to_confirmed(): void
    {
        $service = $this->createActiveService();
        $booking = $this->createBooking($service->id, now()->addDays(9)->toDateString(), '10:00:00', BookingStatus::Pending);

        $serviceLayer = $this->app->make(ReservationDomainService::class);
        $updated = $serviceLayer->changeBookingStatus($booking->id, BookingStatus::Confirmed->value, '확정 처리', null);

        $this->assertSame(BookingStatus::Confirmed->value, $updated->status->value);
        $this->assertNotNull($updated->confirmed_at);
        $this->assertNull($updated->cancelled_at);
    }

    public function test_it_changes_booking_status_from_pending_to_cancelled(): void
    {
        $service = $this->createActiveService();
        $booking = $this->createBooking($service->id, now()->addDays(10)->toDateString(), '10:00:00', BookingStatus::Pending);

        $serviceLayer = $this->app->make(ReservationDomainService::class);
        $updated = $serviceLayer->changeBookingStatus($booking->id, BookingStatus::Cancelled->value, '취소 처리', null);

        $this->assertSame(BookingStatus::Cancelled->value, $updated->status->value);
        $this->assertNotNull($updated->cancelled_at);
    }

    public function test_it_blocks_transition_from_cancelled_to_confirmed(): void
    {
        $service = $this->createActiveService();
        $booking = $this->createBooking($service->id, now()->addDays(11)->toDateString(), '10:00:00', BookingStatus::Cancelled);

        $serviceLayer = $this->app->make(ReservationDomainService::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('취소된 예약은 다시 확정할 수 없습니다.');

        $serviceLayer->changeBookingStatus($booking->id, BookingStatus::Confirmed->value, '재확정 시도', null);
    }

    public function test_it_creates_a_booking_log_when_status_changes(): void
    {
        $service = $this->createActiveService();
        $booking = $this->createBooking($service->id, now()->addDays(12)->toDateString(), '10:00:00', BookingStatus::Pending);

        $serviceLayer = $this->app->make(ReservationDomainService::class);
        $serviceLayer->changeBookingStatus($booking->id, BookingStatus::Confirmed->value, '운영자 확정', null);

        $this->assertDatabaseHas('reservation_booking_logs', [
            'reservation_booking_id' => $booking->id,
            'event_type' => 'status_changed',
            'from_status' => BookingStatus::Pending->value,
            'to_status' => BookingStatus::Confirmed->value,
            'description' => '운영자 확정',
            'logged_by' => null,
        ]);
    }

    private function createActiveService(): ReservationService
    {
        return ReservationService::query()->create([
            'name' => '입학 상담',
            'slug' => 'admin-service-'.Str::lower(Str::random(6)),
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

    private function createBooking(int $serviceId, string $bookingDate, string $bookingTime, BookingStatus $status): ReservationBooking
    {
        return ReservationBooking::query()->create([
            'reservation_service_id' => $serviceId,
            'reservation_schedule_id' => $this->createSchedule($serviceId, now()->parse($bookingDate)->dayOfWeek)->id,
            'booking_code' => 'RSV-'.Str::upper(Str::random(8)),
            'customer_name' => '관리 테스트',
            'customer_phone' => '010'.random_int(10000000, 99999999),
            'booking_date' => $bookingDate,
            'booking_time' => $bookingTime,
            'booking_end_time' => '11:00:00',
            'guest_count' => 1,
            'status' => $status->value,
            'cancelled_at' => $status === BookingStatus::Cancelled ? now() : null,
        ]);
    }

    private function createSchedule(int $serviceId, int $dayOfWeek): ReservationSchedule
    {
        $existing = ReservationSchedule::query()
            ->where('reservation_service_id', $serviceId)
            ->where('day_of_week', $dayOfWeek)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return ReservationSchedule::query()->create([
            'reservation_service_id' => $serviceId,
            'day_of_week' => $dayOfWeek,
            'start_time' => '10:00:00',
            'end_time' => '18:00:00',
            'slot_capacity' => 1,
            'is_active' => true,
        ]);
    }
}
