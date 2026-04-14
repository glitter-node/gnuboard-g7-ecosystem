<?php

namespace Modules\Glitter\Reservation\Http\Resources;

use App\Http\Resources\BaseApiResource;
use Illuminate\Http\Request;

/**
 * 예약 API 리소스
 */
class ReservationResource extends BaseApiResource
{
    /**
     * 배열 변환
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reservation_service_id' => $this->reservation_service_id,
            'reservation_schedule_id' => $this->reservation_schedule_id,
            'booking_code' => $this->booking_code,
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'customer_email' => $this->customer_email,
            'booking_date' => $this->booking_date?->format('Y-m-d'),
            'booking_time' => $this->booking_time?->format('H:i:s'),
            'booking_end_time' => $this->booking_end_time?->format('H:i:s'),
            'guest_count' => $this->guest_count,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'request_memo' => $this->request_memo,
            'admin_memo' => $this->admin_memo,
            'confirmed_at' => $this->confirmed_at?->format('Y-m-d H:i:s'),
            'cancelled_at' => $this->cancelled_at?->format('Y-m-d H:i:s'),
            ...$this->formatTimestamps(),
            ...$this->resourceMeta($request),
        ];
    }

    /**
     * 권한 매핑
     *
     * @return array<string, string>
     */
    protected function abilityMap(): array
    {
        return [
            'can_create' => 'reservation.reservations.create',
            'can_update' => 'reservation.reservations.update',
            'can_delete' => 'reservation.reservations.delete',
        ];
    }

    /**
     * 소유자 필드
     */
    protected function ownerField(): ?string
    {
        return 'created_by';
    }
}
