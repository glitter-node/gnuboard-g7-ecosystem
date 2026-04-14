<?php

namespace Modules\Glitter\Reservation\Http\Controllers;

use App\Http\Controllers\Api\Base\PublicBaseController;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Glitter\Reservation\Http\Requests\CancelBookingRequest;
use Modules\Glitter\Reservation\Http\Requests\LookupBookingRequest;
use Modules\Glitter\Reservation\Http\Requests\SendReservationEmailVerificationRequest;
use Modules\Glitter\Reservation\Http\Requests\SlotAvailabilityRequest;
use Modules\Glitter\Reservation\Http\Requests\StoreReservationRequest;
use Modules\Glitter\Reservation\Http\Resources\CustomerBookingLookupResource;
use Modules\Glitter\Reservation\Http\Resources\PublicReservationServiceResource;
use Modules\Glitter\Reservation\Services\ReservationEmailVerificationService;
use Modules\Glitter\Reservation\Services\ReservationService;
use Modules\Glitter\Reservation\Services\SlotService;
use RuntimeException;
use Throwable;

class ReservationController extends PublicBaseController
{
    public function __construct(
        private SlotService $slotService,
        private ReservationService $reservationService,
        private ReservationEmailVerificationService $reservationEmailVerificationService,
    ) {
        parent::__construct();
    }

    public function services(): JsonResponse
    {
        try {
            $services = $this->reservationService->getPublicServices();

            return $this->successWithResource(
                'common.success',
                PublicReservationServiceResource::collection($services)
            );
        } catch (Throwable $e) {
            return $this->error('예약 가능한 서비스를 불러오지 못했습니다.', 500);
        }
    }

    public function requestEmailVerification(SendReservationEmailVerificationRequest $request): JsonResponse
    {
        try {
            $this->reservationEmailVerificationService->sendVerificationLink(
                (string) $request->validated('email'),
                $request,
            );

            return $this->success('common.success', [
                'sent' => true,
                'message' => '인증 링크를 이메일로 전송했습니다. 메일함을 확인해 주세요.',
            ]);
        } catch (Throwable $e) {
            report($e);

            return $this->error('인증 링크 발송에 실패했습니다.', 500);
        }
    }

    public function verifyEmailVerification(Request $request): JsonResponse|RedirectResponse
    {
        $result = $this->reservationEmailVerificationService->verifyToken(
            (string) $request->query('token', ''),
            $request,
        );

        if ($request->expectsJson()) {
            if ($result['verified'] === true) {
                return $this->success('common.success', $result);
            }

            return $this->error((string) $result['message'], 422);
        }

        $query = http_build_query([
            'verification' => $result['verified'] === true ? 'success' : ($result['code'] ?? 'invalid'),
        ]);

        $path = (string) config('reservation.email_verification.success_redirect_path') ?: config('reservation.public_apply_page_path') ?: '/';

        return redirect($path.($query !== '' ? '?'.$query : ''));
    }

    public function verificationStatus(Request $request): JsonResponse
    {
        return $this->success('common.success', $this->reservationEmailVerificationService->getVerificationStatus($request));
    }

    public function slots(SlotAvailabilityRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $slots = $this->slotService->getAvailableSlots(
                (int) $validated['service_id'],
                (string) $validated['booking_date'],
            );

            return $this->success('common.success', [
                'slots' => $slots,
            ]);
        } catch (Throwable $e) {
            return $this->error('예약 가능한 시간을 불러오지 못했습니다.', 500);
        }
    }

    public function bookings(StoreReservationRequest $request): JsonResponse
    {
        Log::info('[reservation.booking.controller.enter]', [
            'session_id' => $request->session()->getId(),
            'verification_payload' => $request->session()->get('reservation_email_verification'),
            'payload' => $request->all(),
        ]);

        try {
            $payload = $request->validated();
            $verifiedEmail = $this->reservationEmailVerificationService->currentVerifiedEmail($request);

            if ($verifiedEmail === null) {
                return $this->forbidden('이메일 인증을 완료한 뒤 예약을 진행해 주세요.');
            }

            if (
                isset($payload['customer_email'])
                && $payload['customer_email'] !== null
                && mb_strtolower((string) $payload['customer_email']) !== mb_strtolower($verifiedEmail)
            ) {
                return $this->error('이메일 인증 후 동일한 이메일로만 예약할 수 있습니다.', 422);
            }

            $payload['customer_email'] = $verifiedEmail;

            $booking = $this->reservationService->createBooking($payload);

            return $this->success('common.success', [
                'id' => $booking->getKey(),
                'status' => $booking->status?->value,
            ], 201);
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (Throwable $e) {
            report($e);

            return $this->error('예약 생성에 실패했습니다.', 500);
        }
    }

    public function lookupBookings(LookupBookingRequest $request): JsonResponse
    {
        try {
            $bookings = $this->reservationService->lookupBookingsByCustomerPhone(
                (string) $request->validated('customer_phone')
            );

            return $this->successWithResource(
                'common.success',
                CustomerBookingLookupResource::collection($bookings)
            );
        } catch (Throwable $e) {
            report($e);

            return $this->error('예약 조회에 실패했습니다.', 500);
        }
    }

    public function cancelBooking(CancelBookingRequest $request, int $bookingId): JsonResponse
    {
        try {
            $booking = $this->reservationService->cancelBookingByCustomer(
                $bookingId,
                (string) $request->validated('customer_phone')
            );

            return $this->success('common.success', [
                'id' => $booking->getKey(),
                'status' => $booking->status?->value,
                'cancelled_at' => $booking->cancelled_at?->format('Y-m-d H:i:s'),
            ]);
        } catch (ModelNotFoundException) {
            return $this->notFound('예약을 찾을 수 없습니다.');
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (Throwable $e) {
            report($e);

            return $this->error('예약 취소에 실패했습니다.', 500);
        }
    }
}
