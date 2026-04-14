<?php

namespace Modules\Glitter\Reservation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Modules\Glitter\Reservation\Enums\BookingStatus;

class AdminBookingListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['nullable', new Enum(BookingStatus::class)],
            'service_id' => ['nullable', 'integer', 'exists:reservation_services,id'],
            'booking_date' => ['nullable', 'date'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
