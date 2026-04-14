<?php

namespace Modules\Glitter\Reservation\Models;

use Illuminate\Database\Eloquent\Model;

class ReservationEmailVerification extends Model
{
    protected $table = 'reservation_email_verifications';

    protected $fillable = [
        'email',
        'token_hash',
        'expires_at',
        'verified_at',
        'used_at',
        'ip',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }
}
