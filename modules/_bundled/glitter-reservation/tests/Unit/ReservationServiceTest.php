<?php

namespace Modules\Glitter\Reservation\Tests\Unit;

require_once __DIR__.'/../ModuleTestCase.php';

use Illuminate\Support\Str;
use Modules\Glitter\Reservation\Enums\BookingStatus;
use Modules\Glitter\Reservation\Models\ReservationBooking;
use Modules\Glitter\Reservation\Models\ReservationBookingLog;
use Modules\Glitter\Reservation\Models\ReservationSchedule;
use Modules\Glitter\Reservation\Models\ReservationService;
use Modules\Glitter\Reservation\Services\ReservationService as ReservationDomainService;
use Modules\Glitter\Reservation\Tests\ModuleTestCase;
use RuntimeException;

class ReservationServiceTest extends ModuleTestCase
{
    public function test_it_creates_a_booking_and_a_booking_log_when_reservation_creation_succeeds(): void
    {
        $service = $this->createActiveService();
        $bookingDate = now()->addDays(2)->toDateString();

        $this->createSchedule($service->id, now()->addDays(2)->dayOfWeek);

        $domainService = $this->app->make(ReservationDomainService::class);
        $booking = $domainService->createBooking([
            'service_id' => $service->id,
            'booking_date' => $bookingDate,
            'booking_time' => '10:00',
            'customer_name' => '홍길동',
            'customer_phone' => '01011112222',
            'customer_email' => 'hong@example.com',
            'student_grade' => '초등 5학년',
            'memo' => '첫 상담 요청',
        ]);

        $this->assertDatabaseHas('reservation_bookings', [
            'id' => $booking->id,
            'reservation_service_id' => $service->id,
            'booking_date' => $bookingDate,
            'booking_time' => '10:00:00',
            'customer_email' => 'hong@example.com',
            'student_grade' => '초등 5학년',
            'status' => BookingStatus::Pending->value,
        ]);

        $this->assertDatabaseHas('reservation_booking_logs', [
            'reservation_booking_id' => $booking->id,
            'event_type' => 'customer_created',
            'from_status' => null,
            'to_status' => BookingStatus::Pending->value,
            'logged_by' => null,
        ]);

        $log = ReservationBookingLog::query()->where('reservation_booking_id', $booking->id)->first();

        $this->assertNotNull($log);
        $this->assertSame('customer_created', $log->description);
        $this->assertSame('초등 5학년', $booking->fresh()->student_grade);
        $this->assertSame('hong@example.com', $booking->fresh()->customer_email);
        $this->assertSame(1, ReservationBooking::query()->whereKey($booking->id)->count());
        $this->assertSame(1, ReservationBookingLog::query()->where('reservation_booking_id', $booking->id)->count());
    }

    public function test_it_blocks_duplicate_booking_for_the_same_service_date_and_time(): void
    {
        $service = $this->createActiveService();
        $bookingDate = now()->addDays(3)->toDateString();

        $this->createSchedule($service->id, now()->addDays(3)->dayOfWeek);

        ReservationBooking::query()->create([
            'reservation_service_id' => $service->id,
            'booking_code' => 'RSV-'.Str::upper(Str::random(8)),
            'customer_name' => '기존 예약자',
            'customer_phone' => '01099998888',
            'booking_date' => $bookingDate,
            'booking_time' => '11:00:00',
            'booking_end_time' => '12:00:00',
            'guest_count' => 1,
            'status' => BookingStatus::Pending->value,
        ]);

        $domainService = $this->app->make(ReservationDomainService::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('이미 예약이 접수된 시간입니다. 다른 시간을 선택해 주세요.');

        $domainService->createBooking([
            'service_id' => $service->id,
            'booking_date' => $bookingDate,
            'booking_time' => '11:00',
            'customer_name' => '중복 예약자',
            'customer_phone' => '01012341234',
            'student_grade' => null,
            'memo' => null,
        ]);
    }

    private function createActiveService(): ReservationService
    {
        return ReservationService::query()->create([
            'name' => '입학 상담',
            'slug' => 'admission-counseling-'.Str::lower(Str::random(6)),
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
