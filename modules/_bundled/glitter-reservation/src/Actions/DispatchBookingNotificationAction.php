<?php

namespace Modules\Glitter\Reservation\Actions;

use Throwable;
use Modules\Glitter\Reservation\Contracts\NotificationDispatcherInterface;
use Modules\Glitter\Reservation\Data\NotificationMessageData;
use Modules\Glitter\Reservation\Models\ReservationBooking;

class DispatchBookingNotificationAction
{
    public function __construct(
        private NotificationDispatcherInterface $notificationDispatcher,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function handle(
        ReservationBooking $booking,
        string $eventType,
        ?string $oldStatus = null,
        ?string $newStatus = null,
        array $context = [],
    ): void {
        try {
            $context = array_merge([
                'customer_email' => $booking->customer_email,
            ], $context);

            $this->notificationDispatcher->dispatch(
                new NotificationMessageData(
                    bookingId: (int) $booking->getKey(),
                    serviceName: (string) ($booking->service?->name ?? ''),
                    bookingDate: $booking->booking_date?->format('Y-m-d') ?? '',
                    bookingTime: $booking->booking_time?->format('H:i:s') ?? '',
                    customerName: (string) $booking->customer_name,
                    customerPhone: (string) $booking->customer_phone,
                    oldStatus: $oldStatus,
                    newStatus: $newStatus,
                    eventType: $eventType,
                    context: $context,
                )
            );
        } catch (Throwable) {
            // 알림 전송 실패가 예약 저장/상태 변경 흐름을 깨면 안 됩니다.
            // 실제 로깅, 재시도, dead-letter 처리는 이후 플러그인 구현에서 담당합니다.
        }
    }
}
