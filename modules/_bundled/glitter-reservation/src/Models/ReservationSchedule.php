<?php

namespace Modules\Glitter\Reservation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;

/**
 * 예약 스케줄 모델
 */
class ReservationSchedule extends Model
{
    /**
     * 테이블명
     *
     * @var string
     */
    protected $table = 'reservation_schedules';

    /**
     * 대량 할당 가능 속성
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'reservation_service_id',
        'day_of_week',
        'specific_date',
        'start_time',
        'end_time',
        'break_start_time',
        'break_end_time',
        'slot_capacity',
        'is_active',
        'notes',
        'created_by',
        'updated_by',
    ];

    /**
     * 속성 캐스팅
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'specific_date' => 'date',
            'start_time' => 'datetime:H:i:s',
            'end_time' => 'datetime:H:i:s',
            'break_start_time' => 'datetime:H:i:s',
            'break_end_time' => 'datetime:H:i:s',
            'slot_capacity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * 예약 서비스 관계
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(ReservationService::class, 'reservation_service_id');
    }

    /**
     * 예약 목록 관계
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(ReservationBooking::class, 'reservation_schedule_id');
    }

    /**
     * 생성자 관계
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * 수정자 관계
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
