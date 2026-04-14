<?php

namespace Modules\Glitter\Reservation\Providers;

use App\Extension\BaseModuleServiceProvider;
use Modules\Glitter\Reservation\Contracts\NotificationDispatcherInterface;
use Modules\Glitter\Reservation\Notifications\NullNotificationDispatcher;

class ReservationServiceProvider extends BaseModuleServiceProvider
{
    protected string $moduleIdentifier = 'glitter-reservation';

    protected array $repositories = [];

    public function register(): void
    {
        parent::register();

        $this->app->singletonIf(NotificationDispatcherInterface::class, NullNotificationDispatcher::class);
    }

    protected function loadModuleTranslations(): void
    {
        $langPath = dirname(__DIR__, 2).'/resources/lang';

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleIdentifier);
            $this->loadJsonTranslationsFrom($langPath);
        }
    }
}
