<?php

namespace Modules\Glitter\Reservation\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Glitter\Reservation\Enums\BookingStatus;

class ReservationBooking extends Model
{
    protected $table = 'reservation_bookings';

    protected $fillable = [
        'reservation_service_id',
        'reservation_schedule_id',
        'booking_code',
        'customer_name',
        'customer_phone',
        'customer_email',
        'booking_date',
        'booking_time',
        'booking_end_time',
        'guest_count',
        'status',
        'request_memo',
        'admin_memo',
        'confirmed_at',
        'cancelled_at',
        'completed_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'booking_date' => 'date',
            'booking_time' => 'datetime:H:i:s',
            'booking_end_time' => 'datetime:H:i:s',
            'guest_count' => 'integer',
            'status' => BookingStatus::class,
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(ReservationService::class, 'reservation_service_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ReservationSchedule::class, 'reservation_schedule_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ReservationBookingLog::class, 'reservation_booking_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
