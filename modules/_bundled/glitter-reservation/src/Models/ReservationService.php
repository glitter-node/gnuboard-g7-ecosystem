<?php

namespace Modules\Glitter\Reservation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;

/**
 * 예약 서비스 모델
 */
class ReservationService extends Model
{
    /**
     * 테이블명
     *
     * @var string
     */
    protected $table = 'reservation_services';

    /**
     * 대량 할당 가능 속성
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'service_code',
        'name',
        'slug',
        'description',
        'duration_minutes',
        'slot_interval_minutes',
        'buffer_before_minutes',
        'buffer_after_minutes',
        'capacity',
        'price',
        'currency',
        'min_booking_days',
        'max_booking_days',
        'is_active',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    /**
     * 캐스팅
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'slot_interval_minutes' => 'integer',
            'buffer_before_minutes' => 'integer',
            'buffer_after_minutes' => 'integer',
            'capacity' => 'integer',
            'price' => 'decimal:2',
            'min_booking_days' => 'integer',
            'max_booking_days' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * 예약 목록 관계
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(ReservationBooking::class, 'reservation_service_id');
    }

    /**
     * 예약 스케줄 목록 관계
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(ReservationSchedule::class, 'reservation_service_id');
    }

    /**
     * 예약 휴무일 목록 관계
     */
    public function holidays(): HasMany
    {
        return $this->hasMany(ReservationHoliday::class, 'reservation_service_id');
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
