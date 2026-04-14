<?php

namespace Modules\Glitter\Reservation\Repositories;

use Modules\Glitter\Reservation\Models\ReservationEmailVerification;

class EmailVerificationRepository
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ReservationEmailVerification
    {
        return ReservationEmailVerification::query()->create($data);
    }

    public function invalidateOutstandingByEmail(string $email): void
    {
        ReservationEmailVerification::query()
            ->where('email', $email)
            ->whereNull('used_at')
            ->update([
                'used_at' => now(),
            ]);
    }

    public function findByTokenHash(string $tokenHash): ?ReservationEmailVerification
    {
        return ReservationEmailVerification::query()
            ->where('token_hash', $tokenHash)
            ->first();
    }

    public function markVerifiedAndUsed(ReservationEmailVerification $verification): ReservationEmailVerification
    {
        $verification->forceFill([
            'verified_at' => now(),
            'used_at' => now(),
        ])->save();

        return $verification->fresh();
    }
}
