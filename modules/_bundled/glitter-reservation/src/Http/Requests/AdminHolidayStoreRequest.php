<?php

namespace Modules\Glitter\Reservation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminHolidayStoreRequest extends FormRequest
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
            'holiday_date' => ['required', 'date'],
            'name' => ['required', 'string', 'max:150'],
            'is_recurring_yearly' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'holiday_date.required' => '휴무일 날짜를 입력해 주세요.',
            'holiday_date.date' => '휴무일 날짜 형식이 올바르지 않습니다.',
            'name.required' => '휴무일 이름을 입력해 주세요.',
            'name.string' => '휴무일 이름 형식이 올바르지 않습니다.',
            'name.max' => '휴무일 이름은 150자 이하여야 합니다.',
            'is_recurring_yearly.boolean' => '매년 반복 여부 값이 올바르지 않습니다.',
            'notes.string' => '휴무 메모 형식이 올바르지 않습니다.',
        ];
    }
}
