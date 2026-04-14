<?php

namespace Modules\Glitter\Reservation\Database\Seeders;

use Illuminate\Database\Seeder;

class ReservationInstallSeeder extends Seeder
{
    public function run(): void
    {
        if ($this->command !== null) {
            $this->command->info('glitter-reservation install seeder completed with no changes.');
        }
    }
}
