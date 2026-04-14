<?php

namespace Modules\Glitter\Reservation\Http\Resources;

use App\Http\Resources\BaseApiResource;
use Illuminate\Http\Request;

class BookingListResource extends BaseApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'service' => [
                'id' => $this->service?->id,
                'name' => $this->service?->name,
            ],
            'booking_date' => $this->booking_date?->format('Y-m-d'),
            'booking_time' => $this->booking_time?->format('H:i:s'),
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'status' => $this->status?->value,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            ...$this->resourceMeta($request),
        ];
    }
}
