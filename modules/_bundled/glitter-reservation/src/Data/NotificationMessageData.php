<?php

namespace Modules\Glitter\Reservation\Data;

use Modules\Glitter\Reservation\Enums\NotificationChannel;

readonly class NotificationMessageData
{
    /**
     * @param  array<int, NotificationChannel>  $channels
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public int $bookingId,
        public string $serviceName,
        public string $bookingDate,
        public string $bookingTime,
        public string $customerName,
        public string $customerPhone,
        public ?string $oldStatus,
        public ?string $newStatus,
        public string $eventType,
        public array $channels = [],
        public array $context = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'booking_id' => $this->bookingId,
            'service_name' => $this->serviceName,
            'booking_date' => $this->bookingDate,
            'booking_time' => $this->bookingTime,
            'customer_name' => $this->customerName,
            'customer_phone' => $this->customerPhone,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'event_type' => $this->eventType,
            'channels' => array_map(
                static fn (NotificationChannel $channel): string => $channel->value,
                $this->channels,
            ),
            'context' => $this->context,
        ];
    }
}
