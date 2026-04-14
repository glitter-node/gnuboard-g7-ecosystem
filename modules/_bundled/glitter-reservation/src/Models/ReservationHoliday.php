<?php

namespace Modules\Glitter\Reservation\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 예약 휴무일 모델
 */
class ReservationHoliday extends Model
{
    /**
     * 테이블명
     *
     * @var string
     */
    protected $table = 'reservation_holidays';

    /**
     * 대량 할당 가능 속성
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'reservation_service_id',
        'holiday_date',
        'name',
        'is_recurring_yearly',
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
            'holiday_date' => 'date',
            'is_recurring_yearly' => 'boolean',
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
