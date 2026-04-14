<?php

namespace Modules\Glitter\Reservation\Tests\Unit;

require_once __DIR__.'/../ModuleTestCase.php';
require_once dirname(__DIR__, 2).'/src/Contracts/NotificationDispatcherInterface.php';
require_once dirname(__DIR__, 2).'/src/Data/NotificationMessageData.php';

use Modules\Glitter\Reservation\Contracts\NotificationDispatcherInterface;
use Modules\Glitter\Reservation\Data\NotificationMessageData;
use Modules\Glitter\Reservation\Tests\ModuleTestCase;

class PluginNotificationDispatcherTest extends ModuleTestCase
{
    protected static bool $pluginAutoloadRegistered = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerPluginAutoload();
    }

    public function test_it_uses_module_null_dispatcher_before_plugin_provider_is_registered(): void
    {
        $this->app->forgetInstance(NotificationDispatcherInterface::class);
        $this->app->singleton(
            NotificationDispatcherInterface::class,
            \Modules\Glitter\Reservation\Notifications\NullNotificationDispatcher::class,
        );

        $dispatcher = $this->app->make(NotificationDispatcherInterface::class);

        $this->assertInstanceOf(
            \Modules\Glitter\Reservation\Notifications\NullNotificationDispatcher::class,
            $dispatcher,
        );
    }

    public function test_it_overrides_notification_dispatcher_binding_when_plugin_provider_is_registered(): void
    {
        $this->registerPluginProvider();

        $this->assertTrue($this->app->bound('glitter-reservation_notify.provider.loaded'));
        $this->assertTrue($this->app->bound('glitter-reservation_notify.provider.booted'));

        $dispatcher = $this->app->make(NotificationDispatcherInterface::class);

        $this->assertInstanceOf(
            \Plugins\Glitter\ReservationNotify\Dispatchers\ReservationNotificationDispatcher::class,
            $dispatcher,
        );
    }

    public function test_it_resolves_plugin_dispatcher_with_null_sender_fallbacks(): void
    {
        $this->registerPluginProvider();

        $dispatcher = $this->app->make(NotificationDispatcherInterface::class);

        $this->assertInstanceOf(
            \Plugins\Glitter\ReservationNotify\Dispatchers\ReservationNotificationDispatcher::class,
            $dispatcher,
        );
        $this->assertInstanceOf(
            \Plugins\Glitter\ReservationNotify\Senders\SmtpEmailSender::class,
            $this->app->make(\Plugins\Glitter\ReservationNotify\Contracts\EmailSenderInterface::class),
        );
        $this->assertInstanceOf(
            \Plugins\Glitter\ReservationNotify\Senders\NullSmsSender::class,
            $this->app->make(\Plugins\Glitter\ReservationNotify\Contracts\SmsSenderInterface::class),
        );
        $this->assertInstanceOf(
            \Plugins\Glitter\ReservationNotify\Senders\NullAlimtalkSender::class,
            $this->app->make(\Plugins\Glitter\ReservationNotify\Contracts\AlimtalkSenderInterface::class),
        );

        $dispatcher->dispatch(new NotificationMessageData(
            bookingId: 1,
            serviceName: '테스트 상담',
            bookingDate: '2026-04-10',
            bookingTime: '10:00:00',
            customerName: '홍길동',
            customerPhone: '01011112222',
            oldStatus: 'pending',
            newStatus: 'confirmed',
            eventType: 'booking_confirmed',
        ));

        $this->addToAssertionCount(1);
    }

    public function test_it_can_send_real_confirmation_email_when_explicit_test_recipient_is_provided(): void
    {
        $this->registerPluginProvider();

        $realMailTestEnabled = filter_var(
            env('RESERVATION_NOTIFY_REAL_MAIL_TEST', false),
            FILTER_VALIDATE_BOOL,
        );
        $recipientEmail = (string) env('RESERVATION_NOTIFY_TEST_EMAIL', '');

        if (! $realMailTestEnabled || $recipientEmail === '') {
            $this->markTestSkipped('실메일 검증은 RESERVATION_NOTIFY_REAL_MAIL_TEST=true 와 RESERVATION_NOTIFY_TEST_EMAIL 설정 시에만 실행됩니다.');
        }

        $dispatcher = $this->app->make(NotificationDispatcherInterface::class);

        $dispatcher->dispatch(new NotificationMessageData(
            bookingId: 1,
            serviceName: '테스트 상담',
            bookingDate: '2026-04-10',
            bookingTime: '10:00:00',
            customerName: '홍길동',
            customerPhone: '01011112222',
            oldStatus: 'pending',
            newStatus: 'confirmed',
            eventType: 'booking_confirmed',
            context: [
                'customer_email' => $recipientEmail,
            ],
        ));

        $this->addToAssertionCount(1);
    }

    protected function registerPluginAutoload(): void
    {
        if (static::$pluginAutoloadRegistered) {
            return;
        }

        $pluginBasePath = base_path('plugins/glitter-reservation_notify/src/');

        spl_autoload_register(function ($class) use ($pluginBasePath) {
            $prefix = 'Plugins\\Reservation\\Notify\\';
            $len = strlen($prefix);

            if (strncmp($prefix, $class, $len) !== 0) {
                return;
            }

            $relativeClass = substr($class, $len);
            $file = $pluginBasePath.str_replace('\\', '/', $relativeClass).'.php';

            if (file_exists($file)) {
                require $file;
            }
        });

        static::$pluginAutoloadRegistered = true;
    }

    protected function registerPluginProvider(): void
    {
        $this->app->register(\Plugins\Glitter\ReservationNotify\Providers\ReservationNotifyServiceProvider::class);
    }
}
