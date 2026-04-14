<?php

namespace Modules\Glitter\Reservation\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 예약 로그 모델
 */
class ReservationBookingLog extends Model
{
    /**
     * 테이블명
     *
     * @var string
     */
    protected $table = 'reservation_booking_logs';

    /**
     * 대량 할당 가능 속성
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'reservation_booking_id',
        'event_type',
        'from_status',
        'to_status',
        'description',
        'payload',
        'logged_by',
    ];

    /**
     * 속성 캐스팅
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    /**
     * 예약 관계
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(ReservationBooking::class, 'reservation_booking_id');
    }

    /**
     * 기록자 관계
     */
    public function logger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'logged_by');
    }
}
