<?php

namespace Modules\Glitter\Reservation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicReservationServiceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'name' => $this->name,
            'description' => $this->description,
            'duration_minutes' => (int) $this->duration_minutes,
            'slot_interval_minutes' => (int) $this->slot_interval_minutes,
            'min_booking_days' => (int) $this->min_booking_days,
            'max_booking_days' => (int) $this->max_booking_days,
        ];
    }
}
