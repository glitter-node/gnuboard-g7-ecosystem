<?php

namespace Modules\Glitter\Reservation\Upgrades;

use App\Contracts\Extension\UpgradeStepInterface;
use App\Extension\UpgradeContext;

class Upgrade_0_1_0 implements UpgradeStepInterface
{
    public function run(UpgradeContext $context): void
    {
        $context->logger->info('[v0.1.0] No upgrade actions were required.');
    }
}
