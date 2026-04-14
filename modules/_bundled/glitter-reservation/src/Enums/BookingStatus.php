<?php

namespace Modules\Glitter\Reservation\Enums;

/**
 * 예약 상태 Enum
 */
enum BookingStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
    case Completed = 'completed';
    case NoShow = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::Pending => '대기',
            self::Confirmed => '확정',
            self::Cancelled => '취소',
            self::Completed => '완료',
            self::NoShow => '노쇼',
        };
    }
}
