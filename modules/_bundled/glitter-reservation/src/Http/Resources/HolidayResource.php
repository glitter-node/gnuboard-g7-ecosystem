<?php

namespace Modules\Glitter\Reservation\Http\Resources;

use App\Http\Resources\BaseApiResource;
use Illuminate\Http\Request;

class HolidayResource extends BaseApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'holiday_date' => $this->holiday_date?->format('Y-m-d'),
            'name' => $this->name,
            'is_recurring_yearly' => (bool) $this->is_recurring_yearly,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            ...$this->resourceMeta($request),
        ];
    }
}
