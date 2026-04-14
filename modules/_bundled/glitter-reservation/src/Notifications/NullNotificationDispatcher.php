<?php

namespace Modules\Glitter\Reservation\Notifications;

use Modules\Glitter\Reservation\Contracts\NotificationDispatcherInterface;
use Modules\Glitter\Reservation\Data\NotificationMessageData;

class NullNotificationDispatcher implements NotificationDispatcherInterface
{
    public function dispatch(NotificationMessageData $message): void
    {
        unset($message);
    }
}
