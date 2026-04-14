<?php

namespace Modules\Glitter\Reservation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SlotAvailabilityRequest extends FormRequest
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
            'service_id' => ['required', 'integer', 'exists:reservation_services,id'],
            'booking_date' => ['required', 'date', 'after_or_equal:today'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'service_id.required' => '예약 서비스를 선택해 주세요.',
            'service_id.integer' => '예약 서비스 값이 올바르지 않습니다.',
            'service_id.exists' => '선택한 예약 서비스를 찾을 수 없습니다.',
            'booking_date.required' => '예약 날짜를 입력해 주세요.',
            'booking_date.date' => '예약 날짜 형식이 올바르지 않습니다.',
            'booking_date.after_or_equal' => '예약 날짜는 오늘 이후만 선택할 수 있습니다.',
        ];
    }
}
