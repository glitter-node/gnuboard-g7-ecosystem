<?php

namespace Modules\Glitter\Reservation\Tests\Feature;

require_once __DIR__.'/../ModuleTestCase.php';

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Modules\Glitter\Reservation\Enums\BookingStatus;
use Modules\Glitter\Reservation\Http\Controllers\ReservationController;
use Modules\Glitter\Reservation\Http\Middleware\EnsureReservationEmailVerified;
use Modules\Glitter\Reservation\Models\ReservationBooking;
use Modules\Glitter\Reservation\Models\ReservationEmailVerification;
use Modules\Glitter\Reservation\Models\ReservationSchedule;
use Modules\Glitter\Reservation\Models\ReservationService;
use Modules\Glitter\Reservation\Tests\ModuleTestCase;

class ReservationEmailVerificationControllerTest extends ModuleTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutExceptionHandling();

        if (! app('router')->getRoutes()->getByName('reservation.testing.email-verifications.store')) {
            Route::middleware('web')->group(function (): void {
                Route::post('/_testing/reservation/email-verifications', [ReservationController::class, 'requestEmailVerification'])
                    ->name('reservation.testing.email-verifications.store');

                Route::get('/_testing/reservation/email-verifications/verify', [ReservationController::class, 'verifyEmailVerification'])
                    ->name('reservation.testing.email-verifications.verify');

                Route::get('/_testing/reservation/verification-status', [ReservationController::class, 'verificationStatus'])
                    ->name('reservation.testing.verification-status.show');

                Route::post('/_testing/reservation/bookings', [ReservationController::class, 'bookings'])
                    ->middleware(EnsureReservationEmailVerified::class)
                    ->name('reservation.testing.bookings.store');
            });
        }
    }

    public function test_it_creates_email_verification_request(): void
    {
        $response = $this->postJson('/_testing/reservation/email-verifications', [
            'email' => 'hong@example.com',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.sent', true);

        $this->assertDatabaseHas('reservation_email_verifications', [
            'email' => 'hong@example.com',
        ]);
    }

    public function test_resend_invalidates_previous_unused_token(): void
    {
        $first = ReservationEmailVerification::query()->create([
            'email' => 'hong@example.com',
            'token_hash' => hash('sha256', 'old-token'),
            'expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->postJson('/_testing/reservation/email-verifications', [
            'email' => 'hong@example.com',
        ]);

        $response->assertOk();

        $this->assertNotNull($first->fresh()?->used_at);
        $this->assertSame(
            1,
            ReservationEmailVerification::query()
                ->where('email', 'hong@example.com')
                ->whereNull('used_at')
                ->count()
        );
    }

    public function test_verify_success_sets_reservation_verification_session(): void
    {
        $token = 'valid-token';

        ReservationEmailVerification::query()->create([
            'email' => 'hong@example.com',
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addMinutes(15),
        ]);

        $request = $this->makeVerificationRequest($token);

        $verifyResponse = $this->app->make(ReservationController::class)->verifyEmailVerification($request);

        $this->assertSame(200, $verifyResponse->getStatusCode());
        $this->assertSame('hong@example.com', data_get(json_decode($verifyResponse->getContent(), true), 'data.email'));

        $status = $this->app->make(\Modules\Glitter\Reservation\Services\ReservationEmailVerificationService::class)
            ->getVerificationStatus($request);

        $this->assertTrue($status['verified']);
        $this->assertSame('hong@example.com', $status['email']);

        $this->assertNotNull(
            ReservationEmailVerification::query()->where('email', 'hong@example.com')->latest('id')->first()?->used_at
        );
    }

    public function test_expired_token_is_rejected(): void
    {
        $token = 'expired-token';

        ReservationEmailVerification::query()->create([
            'email' => 'expired@example.com',
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->subMinute(),
        ]);

        $request = $this->makeVerificationRequest($token);

        $response = $this->app->make(ReservationController::class)->verifyEmailVerification($request);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('인증 링크가 만료되었습니다. 다시 요청해 주세요.', json_decode($response->getContent(), true)['message'] ?? null);
    }

    public function test_used_token_is_rejected(): void
    {
        $token = 'used-token';

        ReservationEmailVerification::query()->create([
            'email' => 'used@example.com',
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addMinutes(15),
            'used_at' => now(),
        ]);

        $request = $this->makeVerificationRequest($token);

        $response = $this->app->make(ReservationController::class)->verifyEmailVerification($request);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('이미 사용된 인증 링크입니다. 다시 요청해 주세요.', json_decode($response->getContent(), true)['message'] ?? null);
    }

    public function test_booking_store_is_forbidden_without_verified_session(): void
    {
        $service = $this->createService();
        $this->createSchedule($service->id, now()->addDay()->dayOfWeek);
        $phone = '010'.random_int(10000000, 99999999);

        $response = $this->postJson('/_testing/reservation/bookings', [
            'service_id' => $service->id,
            'booking_date' => now()->addDay()->toDateString(),
            'booking_time' => '10:00',
            'customer_name' => '홍길동',
            'customer_phone' => $phone,
            'customer_email' => 'hong@example.com',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false);

        $this->assertDatabaseMissing('reservation_bookings', [
            'customer_phone' => $phone,
        ]);
    }

    public function test_booking_store_succeeds_with_verified_session_and_forces_verified_email(): void
    {
        $service = $this->createService();
        $this->createSchedule($service->id, now()->addDay()->dayOfWeek);
        $phone = '010'.random_int(10000000, 99999999);

        $response = $this->withSession([
            'reservation_email_verification' => [
                'verification_id' => 1,
                'email' => 'verified@example.com',
                'verified_at' => now()->format('Y-m-d H:i:s'),
                'expires_at' => now()->addMinutes(30)->format('Y-m-d H:i:s'),
            ],
        ])->postJson('/_testing/reservation/bookings', [
            'service_id' => $service->id,
            'booking_date' => now()->addDay()->toDateString(),
            'booking_time' => '10:00',
            'customer_name' => '홍길동',
            'customer_phone' => $phone,
            'customer_email' => 'other@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', '이메일 인증 후 동일한 이메일로만 예약할 수 있습니다.');

        $successResponse = $this->withSession([
            'reservation_email_verification' => [
                'verification_id' => 1,
                'email' => 'verified@example.com',
                'verified_at' => now()->format('Y-m-d H:i:s'),
                'expires_at' => now()->addMinutes(30)->format('Y-m-d H:i:s'),
            ],
        ])->postJson('/_testing/reservation/bookings', [
            'service_id' => $service->id,
            'booking_date' => now()->addDay()->toDateString(),
            'booking_time' => '10:00',
            'customer_name' => '홍길동',
            'customer_phone' => $phone,
        ]);

        $successResponse->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', BookingStatus::Pending->value);

        $this->assertDatabaseHas('reservation_bookings', [
            'customer_phone' => $phone,
            'customer_email' => 'verified@example.com',
            'status' => BookingStatus::Pending->value,
        ]);
    }

    private function makeVerificationRequest(string $token): Request
    {
        $request = Request::create('/_testing/reservation/email-verifications/verify', 'GET', [
            'token' => $token,
        ], [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $session = $this->app->make('session')->driver();
        $session->start();
        $request->setLaravelSession($session);

        return $request;
    }

    private function createService(): ReservationService
    {
        return ReservationService::query()->create([
            'name' => '이메일 인증 예약 테스트',
            'slug' => 'email-verify-service-'.substr(md5((string) microtime(true)), 0, 8),
            'duration_minutes' => 60,
            'slot_interval_minutes' => 30,
            'capacity' => 1,
            'price' => 0,
            'currency' => 'KRW',
            'min_booking_days' => 0,
            'max_booking_days' => 90,
            'is_active' => true,
            'sort_order' => 0,
        ]);
    }

    private function createSchedule(int $serviceId, int $dayOfWeek): ReservationSchedule
    {
        return ReservationSchedule::query()->create([
            'reservation_service_id' => $serviceId,
            'day_of_week' => $dayOfWeek,
            'start_time' => '10:00:00',
            'end_time' => '18:00:00',
            'is_active' => true,
        ]);
    }
}
