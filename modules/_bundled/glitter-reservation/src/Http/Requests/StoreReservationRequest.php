<?php

namespace Modules\Glitter\Reservation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_id' => ['required', 'integer', 'exists:reservation_services,id'],
            'booking_date' => ['required', 'date', 'after_or_equal:today'],
            'booking_time' => ['required', 'date_format:H:i'],
            'customer_name' => ['required', 'string', 'max:80'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'memo' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'service_id.required' => '예약 서비스를 선택해 주세요.',
            'service_id.integer' => '예약 서비스 값이 올바르지 않습니다.',
            'service_id.exists' => '선택한 예약 서비스를 찾을 수 없습니다.',
            'booking_date.required' => '예약 날짜를 입력해 주세요.',
            'booking_date.date' => '예약 날짜 형식이 올바르지 않습니다.',
            'booking_date.after_or_equal' => '예약 날짜는 오늘 이후만 선택할 수 있습니다.',
            'booking_time.required' => '예약 시간을 입력해 주세요.',
            'booking_time.date_format' => '예약 시간 형식이 올바르지 않습니다.',
            'customer_name.required' => '예약자 이름을 입력해 주세요.',
            'customer_name.string' => '예약자 이름 형식이 올바르지 않습니다.',
            'customer_name.max' => '예약자 이름은 80자 이하여야 합니다.',
            'customer_phone.required' => '연락처를 입력해 주세요.',
            'customer_phone.string' => '연락처 형식이 올바르지 않습니다.',
            'customer_phone.max' => '연락처는 30자 이하여야 합니다.',
            'customer_email.email' => '이메일 형식이 올바르지 않습니다.',
            'customer_email.max' => '이메일은 255자 이하여야 합니다.',
            'memo.string' => '메모 형식이 올바르지 않습니다.',
        ];
    }
}
