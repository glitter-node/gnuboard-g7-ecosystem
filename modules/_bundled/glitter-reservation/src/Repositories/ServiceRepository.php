<?php

namespace Modules\Glitter\Reservation\Repositories;

use Modules\Glitter\Reservation\Models\ReservationService;

class ServiceRepository
{
    public function getActiveForPublic(): \Illuminate\Database\Eloquent\Collection
    {
        return ReservationService::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function findActiveById(int $id): ?ReservationService
    {
        return ReservationService::query()
            ->whereKey($id)
            ->where('is_active', true)
            ->first();
    }
}
