<?php

namespace Modules\Glitter\Reservation\Tests\Unit;

require_once __DIR__.'/../ModuleTestCase.php';
require_once dirname(__DIR__, 2).'/src/Contracts/NotificationDispatcherInterface.php';
require_once dirname(__DIR__, 2).'/src/Data/NotificationMessageData.php';

use Illuminate\Support\Str;
use Modules\Glitter\Reservation\Contracts\NotificationDispatcherInterface;
use Modules\Glitter\Reservation\Data\NotificationMessageData;
use Modules\Glitter\Reservation\Enums\BookingStatus;
use Modules\Glitter\Reservation\Models\ReservationBooking;
use Modules\Glitter\Reservation\Models\ReservationSchedule;
use Modules\Glitter\Reservation\Models\ReservationService;
use Modules\Glitter\Reservation\Services\ReservationService as ReservationDomainService;
use Modules\Glitter\Reservation\Tests\ModuleTestCase;

class NotificationDispatchTest extends ModuleTestCase
{
    public function test_it_dispatches_booking_created_event_when_booking_is_created(): void
    {
        $spy = new NotificationDispatcherSpy();
        $this->app->instance(NotificationDispatcherInterface::class, $spy);

        $service = $this->createActiveService();
        $bookingDate = now()->addDays(2);

        $this->createSchedule($service->id, $bookingDate->dayOfWeek);

        $domainService = $this->app->make(ReservationDomainService::class);
        $booking = $domainService->createBooking([
            'service_id' => $service->id,
            'booking_date' => $bookingDate->toDateString(),
            'booking_time' => '10:00',
            'customer_name' => '홍길동',
            'customer_phone' => '01011112222',
            'customer_email' => 'hong@example.com',
            'student_grade' => '중등 1학년',
            'memo' => '알림 테스트',
        ]);

        $this->assertCount(1, $spy->messages);
        $this->assertSame('booking_created', $spy->messages[0]->eventType);
        $this->assertSame($booking->id, $spy->messages[0]->bookingId);
        $this->assertSame(BookingStatus::Pending->value, $spy->messages[0]->newStatus);
        $this->assertSame('hong@example.com', $spy->messages[0]->context['customer_email'] ?? null);
    }

    public function test_it_dispatches_booking_confirmed_event_when_admin_confirms_booking(): void
    {
        $spy = new NotificationDispatcherSpy();
        $this->app->instance(NotificationDispatcherInterface::class, $spy);

        $service = $this->createActiveService();
        $booking = $this->createBooking($service->id, now()->addDays(4)->toDateString(), '10:00:00', BookingStatus::Pending);

        $domainService = $this->app->make(ReservationDomainService::class);
        $domainService->changeBookingStatus($booking->id, BookingStatus::Confirmed->value, '확정', null);

        $this->assertCount(1, $spy->messages);
        $this->assertSame('booking_confirmed', $spy->messages[0]->eventType);
        $this->assertSame(BookingStatus::Pending->value, $spy->messages[0]->oldStatus);
        $this->assertSame(BookingStatus::Confirmed->value, $spy->messages[0]->newStatus);
    }

    public function test_it_dispatches_customer_cancelled_event_when_customer_cancels_booking(): void
    {
        $spy = new NotificationDispatcherSpy();
        $this->app->instance(NotificationDispatcherInterface::class, $spy);

        $service = $this->createActiveService();
        $booking = $this->createBooking($service->id, now()->addDays(5)->toDateString(), '10:00:00', BookingStatus::Confirmed);

        $domainService = $this->app->make(ReservationDomainService::class);
        $domainService->cancelBookingByCustomer($booking->id, $booking->customer_phone);

        $this->assertCount(1, $spy->messages);
        $this->assertSame('customer_cancelled', $spy->messages[0]->eventType);
        $this->assertSame(BookingStatus::Confirmed->value, $spy->messages[0]->oldStatus);
        $this->assertSame(BookingStatus::Cancelled->value, $spy->messages[0]->newStatus);
    }

    public function test_it_still_works_with_default_null_dispatcher_when_no_real_dispatcher_is_bound(): void
    {
        $this->app->forgetInstance(NotificationDispatcherInterface::class);

        $service = $this->createActiveService();
        $bookingDate = now()->addDays(6);

        $this->createSchedule($service->id, $bookingDate->dayOfWeek);

        $domainService = $this->app->make(ReservationDomainService::class);
        $booking = $domainService->createBooking([
            'service_id' => $service->id,
            'booking_date' => $bookingDate->toDateString(),
            'booking_time' => '10:00',
            'customer_name' => '기본 디스패처 테스트',
            'customer_phone' => '01033334444',
            'customer_email' => 'default@example.com',
            'student_grade' => null,
            'memo' => null,
        ]);

        $this->assertSame(BookingStatus::Pending->value, $booking->status->value);
        $this->assertDatabaseHas('reservation_bookings', [
            'id' => $booking->id,
            'status' => BookingStatus::Pending->value,
        ]);
    }

    private function createActiveService(): ReservationService
    {
        return ReservationService::query()->create([
            'name' => '알림 테스트 상담',
            'slug' => 'notify-service-'.Str::lower(Str::random(6)),
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

    private function createBooking(int $serviceId, string $bookingDate, string $bookingTime, BookingStatus $status): ReservationBooking
    {
        return ReservationBooking::query()->create([
            'reservation_service_id' => $serviceId,
            'reservation_schedule_id' => $this->createSchedule($serviceId, now()->parse($bookingDate)->dayOfWeek)->id,
            'booking_code' => 'NTF-'.Str::upper(Str::random(8)),
            'customer_name' => '알림 테스트',
            'customer_phone' => '010'.random_int(10000000, 99999999),
            'booking_date' => $bookingDate,
            'booking_time' => $bookingTime,
            'booking_end_time' => '11:00:00',
            'guest_count' => 1,
            'status' => $status->value,
            'confirmed_at' => $status === BookingStatus::Confirmed ? now() : null,
            'cancelled_at' => $status === BookingStatus::Cancelled ? now() : null,
            'completed_at' => $status === BookingStatus::Completed ? now() : null,
        ]);
    }
}

class NotificationDispatcherSpy implements NotificationDispatcherInterface
{
    /**
     * @var array<int, NotificationMessageData>
     */
    public array $messages = [];

    public function dispatch(NotificationMessageData $message): void
    {
        $this->messages[] = $message;
    }
}
