<?php

namespace Modules\Glitter\Reservation\Contracts;

use Modules\Glitter\Reservation\Data\NotificationMessageData;

interface NotificationDispatcherInterface
{
    public function dispatch(NotificationMessageData $message): void;
}
