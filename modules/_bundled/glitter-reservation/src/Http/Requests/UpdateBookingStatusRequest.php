<?php

namespace Modules\Glitter\Reservation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Modules\Glitter\Reservation\Enums\BookingStatus;

/**
 * 예약 상태 변경 요청
 */
class UpdateBookingStatusRequest extends FormRequest
{
    /**
     * 권한 확인
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 검증 규칙
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', new Enum(BookingStatus::class)],
            'admin_memo' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => '예약 상태를 선택해 주세요.',
            'status.string' => '예약 상태 값이 올바르지 않습니다.',
            'admin_memo.string' => '관리자 메모 형식이 올바르지 않습니다.',
            'admin_memo.max' => '관리자 메모는 1000자 이하여야 합니다.',
        ];
    }
}
