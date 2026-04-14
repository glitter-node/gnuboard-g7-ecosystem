<?php

namespace Modules\Glitter\Reservation\Http\Controllers\Admin;

use App\Http\Controllers\Api\Base\AdminBaseController;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Modules\Glitter\Reservation\Http\Requests\AdminBookingListRequest;
use Modules\Glitter\Reservation\Http\Requests\UpdateBookingStatusRequest;
use Modules\Glitter\Reservation\Http\Resources\BookingDetailResource;
use Modules\Glitter\Reservation\Http\Resources\BookingListResource;
use Modules\Glitter\Reservation\Services\ReservationService;
use RuntimeException;

class ReservationController extends AdminBaseController
{
    public function __construct(private ReservationService $reservationService)
    {
        parent::__construct();
    }

    public function index(AdminBookingListRequest $request): JsonResponse
    {
        try {
            $bookings = $this->reservationService->getAdminBookings($request->validated());

            return $this->successWithResource(
                'common.success',
                BookingListResource::collection($bookings)
            );
        } catch (\Throwable $e) {
            return $this->error('예약 목록을 불러오지 못했습니다.', 500, $e->getMessage());
        }
    }

    public function show(int $bookingId): JsonResponse
    {
        try {
            return $this->successWithResource(
                'common.success',
                new BookingDetailResource($this->reservationService->getAdminBooking($bookingId))
            );
        } catch (ModelNotFoundException) {
            return $this->notFound('예약을 찾을 수 없습니다.');
        } catch (\Throwable $e) {
            return $this->error('예약 상세를 불러오지 못했습니다.', 500, $e->getMessage());
        }
    }

    public function updateStatus(UpdateBookingStatusRequest $request, int $bookingId): JsonResponse
    {
        try {
            return $this->successWithResource(
                'common.success',
                new BookingDetailResource(
                    $this->reservationService->changeBookingStatus(
                        $bookingId,
                        (string) $request->validated('status'),
                        $request->validated('admin_memo'),
                        $this->getCurrentUser()?->getKey(),
                    )
                )
            );
        } catch (ModelNotFoundException) {
            return $this->notFound('예약을 찾을 수 없습니다.');
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (\Throwable $e) {
            return $this->error('예약 상태를 변경하지 못했습니다.', 500, $e->getMessage());
        }
    }
}
