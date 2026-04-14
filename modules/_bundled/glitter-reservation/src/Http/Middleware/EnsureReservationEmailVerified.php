<?php

namespace Modules\Glitter\Reservation\Http\Middleware;

use App\Helpers\ResponseHelper;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Glitter\Reservation\Services\ReservationEmailVerificationService;
use Symfony\Component\HttpFoundation\Response;

class EnsureReservationEmailVerified
{
    public function __construct(
        private ReservationEmailVerificationService $reservationEmailVerificationService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $isVerified = $this->reservationEmailVerificationService->isVerified($request);

        Log::info('[reservation.booking.middleware]', [
            'session_id' => $request->hasSession() ? $request->session()->getId() : null,
            'verification_payload' => $request->hasSession()
                ? $request->session()->get('reservation_email_verification')
                : null,
            'is_verified' => $isVerified,
        ]);

        if (! $isVerified) {
            return ResponseHelper::forbidden('이메일 인증이 완료된 사용자만 예약할 수 있습니다.');
        }

        return $next($request);
    }
}
