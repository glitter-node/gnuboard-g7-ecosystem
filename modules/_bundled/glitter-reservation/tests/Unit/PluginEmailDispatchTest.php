<?php

namespace Modules\Glitter\Reservation\Tests\Unit;

require_once __DIR__.'/../ModuleTestCase.php';
require_once dirname(__DIR__, 2).'/src/Data/NotificationMessageData.php';

use Modules\Glitter\Reservation\Data\NotificationMessageData;
use Modules\Glitter\Reservation\Enums\NotificationChannel;
use Modules\Glitter\Reservation\Tests\ModuleTestCase;

class PluginEmailDispatchTest extends ModuleTestCase
{
    protected static bool $pluginAutoloadRegistered = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerPluginAutoload();
    }

    public function test_booking_confirmed_event_routes_to_email_channel(): void
    {
        $policy = \Plugins\Glitter\ReservationNotify\Support\NotificationPolicy::forEvent('booking_confirmed');

        $this->assertContains(NotificationChannel::Email, $policy['channels']);
        $this->assertSame('reservation.booking_confirmed', $policy['template_key']);
    }

    public function test_dispatcher_gracefully_skips_email_when_customer_email_is_missing(): void
    {
        $emailSpy = new PluginEmailSenderSpy();

        $dispatcher = new \Plugins\Glitter\ReservationNotify\Dispatchers\ReservationNotificationDispatcher(
            new PluginSmsSenderNullStub(),
            $emailSpy,
            new PluginAlimtalkSenderNullStub(),
        );

        $dispatcher->dispatch($this->makeNotificationMessage('booking_confirmed'));

        $this->assertCount(0, $emailSpy->calls);
    }

    public function test_email_message_factory_builds_subject_and_body_when_customer_email_exists(): void
    {
        $message = $this->makeNotificationMessage('booking_confirmed', [
            'customer_email' => 'hong@example.com',
        ]);

        $emailMessage = \Plugins\Glitter\ReservationNotify\Support\EmailMessageFactory::make($message);

        $this->assertSame('[예약] 예약이 확정되었습니다', $emailMessage['subject']);
        $this->assertStringContainsString('홍길동님, 안녕하세요.', $emailMessage['body']);
        $this->assertStringContainsString('예약이 확정되었습니다.', $emailMessage['body']);
        $this->assertStringContainsString('서비스: 테스트 상담', $emailMessage['body']);
    }

    public function test_dispatcher_calls_email_sender_when_customer_email_exists(): void
    {
        $emailSpy = new PluginEmailSenderSpy();

        $dispatcher = new \Plugins\Glitter\ReservationNotify\Dispatchers\ReservationNotificationDispatcher(
            new PluginSmsSenderNullStub(),
            $emailSpy,
            new PluginAlimtalkSenderNullStub(),
        );

        $message = $this->makeNotificationMessage('booking_confirmed', [
            'customer_email' => 'hong@example.com',
        ]);
        $expectedEmailMessage = \Plugins\Glitter\ReservationNotify\Support\EmailMessageFactory::make($message);

        $dispatcher->dispatch($message);

        $this->assertCount(1, $emailSpy->calls);
        $this->assertSame('hong@example.com', $emailSpy->calls[0]['to']);
        $this->assertSame($expectedEmailMessage['subject'], $emailSpy->calls[0]['subject']);
        $this->assertSame($expectedEmailMessage['body'], $emailSpy->calls[0]['payload']['message']['body'] ?? null);
        $this->assertSame('reservation.booking_confirmed', $emailSpy->calls[0]['payload']['template_key'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function makeNotificationMessage(string $eventType, array $context = []): NotificationMessageData
    {
        return new NotificationMessageData(
            bookingId: 1,
            serviceName: '테스트 상담',
            bookingDate: '2026-04-10',
            bookingTime: '10:00:00',
            customerName: '홍길동',
            customerPhone: '01011112222',
            oldStatus: 'pending',
            newStatus: 'confirmed',
            eventType: $eventType,
            context: $context,
        );
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
}

class PluginEmailSenderSpy implements \Plugins\Glitter\ReservationNotify\Contracts\EmailSenderInterface
{
    /**
     * @var array<int, array{to: string, subject: string, payload: array<string, mixed>}>
     */
    public array $calls = [];

    /**
     * @param  array<string, mixed>  $payload
     */
    public function send(string $to, string $subject, array $payload = []): void
    {
        $this->calls[] = [
            'to' => $to,
            'subject' => $subject,
            'payload' => $payload,
        ];
    }
}

class PluginSmsSenderNullStub implements \Plugins\Glitter\ReservationNotify\Contracts\SmsSenderInterface
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function send(string $to, string $templateOrMessage, array $payload = []): void
    {
        unset($to, $templateOrMessage, $payload);
    }
}

class PluginAlimtalkSenderNullStub implements \Plugins\Glitter\ReservationNotify\Contracts\AlimtalkSenderInterface
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function send(string $to, string $templateCode, array $payload = []): void
    {
        unset($to, $templateCode, $payload);
    }
}
