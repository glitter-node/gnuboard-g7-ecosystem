<?php

namespace Modules\Glitter\Reservation\Enums;

enum NotificationChannel: string
{
    case Sms = 'sms';
    case Email = 'email';
    case Alimtalk = 'alimtalk';
    case Internal = 'internal';
}
