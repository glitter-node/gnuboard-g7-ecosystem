<?php

namespace Modules\Glitter\Reservation\Http\Resources;

use App\Http\Resources\BaseApiResource;
use Illuminate\Http\Request;

class BookingDetailResource extends BaseApiResource
{
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
            'customer_email' => $this->customer_email,
            'status' => $this->status?->value,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'memo' => $this->request_memo,
            'confirmed_at' => $this->confirmed_at?->format('Y-m-d H:i:s'),
            'cancelled_at' => $this->cancelled_at?->format('Y-m-d H:i:s'),
            'completed_at' => $this->completed_at?->format('Y-m-d H:i:s'),
            'booking_logs' => $this->whenLoaded('logs', function () {
                return $this->logs
                    ->sortBy('created_at')
                    ->values()
                    ->map(static fn ($log) => [
                        'from_status' => $log->from_status,
                        'to_status' => $log->to_status,
                        'note' => $log->description,
                        'created_at' => $log->created_at?->format('Y-m-d H:i:s'),
                    ]);
            }),
            ...$this->resourceMeta($request),
        ];
    }
}
