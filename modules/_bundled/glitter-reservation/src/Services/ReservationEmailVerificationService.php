<?php

namespace Modules\Glitter\Reservation\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Modules\Glitter\Reservation\Repositories\EmailVerificationRepository;

class ReservationEmailVerificationService
{
    private const SESSION_KEY = 'reservation_email_verification';

    private const COOKIE_KEY = 'reservation_email_verification';

    private const VERIFY_TTL_MINUTES = 15;

    private const ACCESS_TTL_MINUTES = 30;

    public function __construct(
        private EmailVerificationRepository $emailVerificationRepository,
    ) {}

    public function sendVerificationLink(string $email, Request $request): void
    {
        $normalizedEmail = mb_strtolower(trim($email));

        $this->emailVerificationRepository->invalidateOutstandingByEmail($normalizedEmail);
        $this->clearVerificationState($request);

        $token = bin2hex(random_bytes(32));
        $verification = $this->emailVerificationRepository->create([
            'email' => $normalizedEmail,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addMinutes(self::VERIFY_TTL_MINUTES),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $verifyUrl = url('/modules/glitter-reservation/reservation/email-verifications/verify?token='.$token);
        $fromAddress = $this->resolveSenderAddress();
        $fromName = (string) config('mail.from.name', 'Glitter Reservation');

        Mail::raw($this->buildMailBody($verifyUrl), function ($message) use ($normalizedEmail, $fromAddress, $fromName): void {
            if ($fromAddress !== null) {
                $message->from($fromAddress, $fromName);
            }

            $message->to($normalizedEmail)
                ->subject('예약 진행을 위한 이메일 인증');

            $message->getHeaders()->addTextHeader('X-G7-Source', 'reservation_email_verification');
            $message->getHeaders()->addTextHeader('X-G7-Extension-Type', 'module');
            $message->getHeaders()->addTextHeader('X-G7-Extension-Id', 'glitter-reservation');
        });
        unset($verification);
    }

    /**
     * @return array{verified: bool, email: string|null, expires_at: string|null, code?: string, message: string}
     */
    public function verifyToken(string $token, Request $request): array
    {
        if ($token === '') {
            return [
                'verified' => false,
                'email' => null,
                'expires_at' => null,
                'code' => 'invalid',
                'message' => '유효하지 않은 인증 링크입니다.',
            ];
        }

        $verification = $this->emailVerificationRepository->findByTokenHash(hash('sha256', $token));

        if ($verification === null) {
            return [
                'verified' => false,
                'email' => null,
                'expires_at' => null,
                'code' => 'invalid',
                'message' => '유효하지 않은 인증 링크입니다.',
            ];
        }

        if ($verification->used_at !== null) {
            return [
                'verified' => false,
                'email' => $verification->email,
                'expires_at' => $verification->expires_at?->format('Y-m-d H:i:s'),
                'code' => 'used',
                'message' => '이미 사용된 인증 링크입니다. 다시 요청해 주세요.',
            ];
        }

        if ($verification->expires_at !== null && $verification->expires_at->isPast()) {
            return [
                'verified' => false,
                'email' => $verification->email,
                'expires_at' => $verification->expires_at?->format('Y-m-d H:i:s'),
                'code' => 'expired',
                'message' => '인증 링크가 만료되었습니다. 다시 요청해 주세요.',
            ];
        }

        $verification = $this->emailVerificationRepository->markVerifiedAndUsed($verification);
        $accessExpiresAt = now()->addMinutes(self::ACCESS_TTL_MINUTES);
        $payload = [
            'verification_id' => $verification->getKey(),
            'email' => $verification->email,
            'verified_at' => now()->format('Y-m-d H:i:s'),
            'expires_at' => $accessExpiresAt->format('Y-m-d H:i:s'),
        ];

        $this->storeVerificationState($request, $payload);

        Log::info('[reservation.verify.success]', [
            'session_id' => $request->session()->getId(),
            'payload' => $payload,
        ]);

        return [
            'verified' => true,
            'email' => $verification->email,
            'expires_at' => $accessExpiresAt->format('Y-m-d H:i:s'),
            'message' => '이메일 인증이 완료되었습니다.',
        ];
    }

    /**
     * @return array{verified: bool, email: string|null, expires_at: string|null}
     */
    public function getVerificationStatus(Request $request): array
    {
        if (! $this->isVerified($request)) {
            return [
                'verified' => false,
                'email' => null,
                'expires_at' => null,
            ];
        }

        $payload = $request->session()->get(self::SESSION_KEY, []);

        return [
            'verified' => true,
            'email' => (string) ($payload['email'] ?? ''),
            'expires_at' => (string) ($payload['expires_at'] ?? ''),
        ];
    }

    public function isVerified(Request $request): bool
    {
        $payload = $this->getStoredVerificationPayload($request);

        Log::info('[reservation.verify.check]', [
            'session_id' => $request->hasSession() ? $request->session()->getId() : null,
            'payload' => $payload,
        ]);

        if (
            ! is_array($payload)
            || empty($payload['verification_id'])
            || empty($payload['email'])
            || empty($payload['expires_at'])
        ) {
            return false;
        }

        $expiresAt = Carbon::parse((string) $payload['expires_at']);

        if ($expiresAt->isPast()) {
            $this->clearVerificationState($request);

            Log::warning('[reservation.verify.expired]', [
                'session_id' => $request->hasSession() ? $request->session()->getId() : null,
                'payload' => $payload,
            ]);

            return false;
        }

        return true;
    }

    public function currentVerifiedEmail(Request $request): ?string
    {
        if (! $this->isVerified($request)) {
            return null;
        }

        $payload = $this->getStoredVerificationPayload($request) ?? [];
        $email = (string) ($payload['email'] ?? '');

        Log::info('[reservation.verify.current_email]', [
            'session_id' => $request->hasSession() ? $request->session()->getId() : null,
            'email' => $email,
        ]);

        return $email !== '' ? $email : null;
    }

    private function buildMailBody(string $verifyUrl): string
    {
        return implode("\n", [
            '예약 진행을 위한 이메일 인증 링크입니다.',
            '',
            '아래 링크를 클릭하면 예약 진행이 가능합니다.',
            $verifyUrl,
            '',
            '인증 링크 유효 시간: 15분',
            '인증 완료 후 예약 가능 시간: 30분',
            '',
            '본인이 요청하지 않았다면 이 메일을 무시해 주세요.',
        ]);
    }

    private function resolveSenderAddress(): ?string
    {
        $smtpUsername = trim((string) config('mail.mailers.smtp.username', ''));

        if ($smtpUsername !== '' && filter_var($smtpUsername, FILTER_VALIDATE_EMAIL) !== false) {
            return $smtpUsername;
        }

        $fromAddress = trim((string) config('mail.from.address', ''));

        if ($fromAddress !== '' && filter_var($fromAddress, FILTER_VALIDATE_EMAIL) !== false) {
            return $fromAddress;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function storeVerificationState(Request $request, array $payload): void
    {
        $request->session()->regenerate(true);
        $request->session()->put(self::SESSION_KEY, $payload);
        $request->session()->save();

        Cookie::queue(cookie(
            self::COOKIE_KEY,
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            self::ACCESS_TTL_MINUTES,
            '/',
            config('session.domain'),
            (bool) config('session.secure'),
            true,
            false,
            config('session.same_site')
        ));
    }

    private function clearVerificationState(Request $request): void
    {
        $request->session()->forget(self::SESSION_KEY);
        $request->session()->save();

        Cookie::queue(Cookie::forget(
            self::COOKIE_KEY,
            '/',
            config('session.domain')
        ));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getStoredVerificationPayload(Request $request): ?array
    {
        $sessionPayload = $request->session()->get(self::SESSION_KEY);

        if (is_array($sessionPayload)) {
            return $sessionPayload;
        }

        $cookiePayload = $request->cookie(self::COOKIE_KEY);

        if (! is_string($cookiePayload) || $cookiePayload === '') {
            return null;
        }

        $decoded = json_decode($cookiePayload, true);

        if (! is_array($decoded)) {
            return null;
        }

        $request->session()->put(self::SESSION_KEY, $decoded);
        $request->session()->save();

        Log::info('[reservation.verify.rehydrated_from_cookie]', [
            'session_id' => $request->hasSession() ? $request->session()->getId() : null,
            'payload' => $decoded,
        ]);

        return $decoded;
    }
}
