<?php

namespace Modules\Glitter\Reservation\Services;

use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Glitter\Reservation\Actions\BookingStatusChangedAction;
use Modules\Glitter\Reservation\Actions\DispatchBookingNotificationAction;
use Modules\Glitter\Reservation\Enums\BookingStatus;
use Modules\Glitter\Reservation\Models\ReservationBooking;
use Modules\Glitter\Reservation\Repositories\BookingLogRepository;
use Modules\Glitter\Reservation\Repositories\BookingRepository;
use Modules\Glitter\Reservation\Repositories\HolidayRepository;
use Modules\Glitter\Reservation\Repositories\ServiceRepository;
use RuntimeException;

class ReservationService
{
    public function __construct(
        private BookingStatusChangedAction $bookingStatusChangedAction,
        private DispatchBookingNotificationAction $dispatchBookingNotificationAction,
        private BookingLogRepository $bookingLogRepository,
        private BookingRepository $bookingRepository,
        private ServiceRepository $serviceRepository,
        private HolidayRepository $holidayRepository,
        private SlotService $slotService,
    ) {}

    public function getAdminBookings(array $filters = []): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 15);

        return $this->bookingRepository->paginateForAdmin($filters, max($perPage, 1));
    }

    public function getPublicServices(): Collection
    {
        return $this->serviceRepository->getActiveForPublic();
    }

    public function getAdminBooking(int $bookingId): ReservationBooking
    {
        $booking = $this->bookingRepository->findDetailedById($bookingId);

        if ($booking === null) {
            throw new ModelNotFoundException('예약을 찾을 수 없습니다.');
        }

        return $booking;
    }

    public function lookupBookingsByCustomerPhone(string $customerPhone): Collection
    {
        return $this->bookingRepository->findLookupBookingsByPhone($customerPhone);
    }

    public function cancelBookingByCustomer(int $bookingId, string $customerPhone): ReservationBooking
    {
        $result = DB::transaction(function () use ($bookingId, $customerPhone): array {
            $booking = $this->bookingRepository->findByIdAndPhoneForUpdate($bookingId, $customerPhone);

            if ($booking === null) {
                throw new ModelNotFoundException('예약을 찾을 수 없습니다.');
            }

            $currentStatus = $booking->status;

            if (! $currentStatus instanceof BookingStatus) {
                throw new RuntimeException('현재 예약 상태가 올바르지 않습니다.');
            }

            if (! in_array($currentStatus, [BookingStatus::Pending, BookingStatus::Confirmed], true)) {
                throw new RuntimeException($this->customerCancellationBlockedMessage($currentStatus));
            }

            $updatedBooking = $this->bookingRepository->updateStatus($booking->getKey(), BookingStatus::Cancelled->value, [
                'cancelled_at' => now(),
            ]);

            if ($updatedBooking === null) {
                throw new ModelNotFoundException('예약을 찾을 수 없습니다.');
            }

            $this->bookingLogRepository->createCustomerCancelledLog(
                $updatedBooking->getKey(),
                $currentStatus->value,
                $customerPhone,
            );

            return [
                'booking' => $updatedBooking,
                'previous_status' => $currentStatus,
            ];
        });

        $booking = $result['booking'];
        $previousStatus = $result['previous_status'];

        $this->runCustomerCancelledNotificationAction($booking, $previousStatus);

        return $booking;
    }

    public function updateBookingStatus(int $bookingId, array $payload): ReservationBooking
    {
        return $this->changeBookingStatus(
            $bookingId,
            (string) $payload['status'],
            $payload['admin_memo'] ?? null,
            Auth::id(),
        );
    }

    public function changeBookingStatus(
        int $bookingId,
        string $newStatus,
        ?string $adminMemo = null,
        ?int $actorUserId = null,
    ): ReservationBooking {
        $targetStatus = BookingStatus::from($newStatus);

        $result = DB::transaction(function () use ($bookingId, $targetStatus, $adminMemo, $actorUserId): array {
            $booking = $this->bookingRepository->findByIdForUpdate($bookingId);

            if ($booking === null) {
                throw new ModelNotFoundException('예약을 찾을 수 없습니다.');
            }

            $currentStatus = $booking->status;

            if (! $currentStatus instanceof BookingStatus) {
                throw new RuntimeException('현재 예약 상태가 올바르지 않습니다.');
            }

            if (! $this->canTransitionStatus($currentStatus, $targetStatus)) {
                throw new RuntimeException($this->invalidStatusTransitionMessage($currentStatus, $targetStatus));
            }

            $updatedBooking = $this->bookingRepository->updateStatus($booking->getKey(), $targetStatus->value, [
                'admin_memo' => $adminMemo ?? $booking->admin_memo,
                'confirmed_at' => $targetStatus === BookingStatus::Confirmed ? now() : $booking->confirmed_at,
                'cancelled_at' => $targetStatus === BookingStatus::Cancelled ? now() : null,
                'completed_at' => $targetStatus === BookingStatus::Completed ? now() : null,
                'updated_by' => $actorUserId,
            ]);

            if ($updatedBooking === null) {
                throw new ModelNotFoundException('예약을 찾을 수 없습니다.');
            }

            $this->bookingLogRepository->createStatusLog(
                $updatedBooking->getKey(),
                $currentStatus->value,
                $targetStatus->value,
                $actorUserId,
                $adminMemo,
                ['admin_memo' => $adminMemo]
            );

            return [
                'booking' => $updatedBooking,
                'previous_status' => $currentStatus,
            ];
        });

        $booking = $result['booking'];
        $previousStatus = $result['previous_status'];

        $this->runBookingStatusChangedAction($booking, $previousStatus, $targetStatus);

        return $booking;
    }

    public function createBooking(array $payload): ReservationBooking
    {
        $serviceId = (int) ($payload['service_id'] ?? 0);
        $bookingDate = (string) ($payload['booking_date'] ?? '');
        $bookingTime = (string) ($payload['booking_time'] ?? '');

        $service = $this->serviceRepository->findActiveById($serviceId);

        if ($service === null) {
            throw new RuntimeException('예약 가능한 서비스를 찾을 수 없습니다.');
        }

        if ($this->holidayRepository->isHoliday($bookingDate)) {
            throw new RuntimeException('선택한 날짜는 예약이 불가능한 휴무일입니다.');
        }

        if ($this->bookingRepository->existsConfirmedOrPendingAt($serviceId, $bookingDate, $bookingTime)) {
            throw new RuntimeException($this->duplicateBookingMessage());
        }

        $availableSlots = $this->slotService->getAvailableSlots($serviceId, $bookingDate);

        if (! in_array($bookingTime, $availableSlots, true)) {
            throw new RuntimeException('선택한 시간은 예약 가능한 슬롯이 아닙니다.');
        }

        try {
            $booking = DB::transaction(function () use ($payload, $service, $serviceId, $bookingDate, $bookingTime): ReservationBooking {
                if ($this->bookingRepository->existsConfirmedOrPendingAt($serviceId, $bookingDate, $bookingTime)) {
                    throw new RuntimeException($this->duplicateBookingMessage());
                }

                $booking = $this->bookingRepository->create([
                    'reservation_service_id' => $service->getKey(),
                    'booking_code' => $this->generateBookingCode(),
                    'customer_name' => $payload['customer_name'],
                    'customer_phone' => $payload['customer_phone'],
                    'customer_email' => $payload['customer_email'] ?? null,
                    'booking_date' => $bookingDate,
                    'booking_time' => $bookingTime,
                    'booking_end_time' => $this->calculateBookingEndTime($bookingDate, $bookingTime, (int) $service->duration_minutes),
                    'status' => BookingStatus::Pending->value,
                    'request_memo' => $payload['memo'] ?? null,
                    'admin_memo' => null,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);

                $this->bookingLogRepository->createCustomerCreatedLog($booking, [
                    'service_id' => $service->getKey(),
                    'service_name' => $service->name,
                    'booking_date' => $bookingDate,
                    'booking_time' => $bookingTime,
                    'customer_phone' => $payload['customer_phone'],
                    'customer_email' => $payload['customer_email'] ?? null,
                ]);

                return $booking;
            });

            $this->runBookingCreatedNotificationAction($booking);

            return $booking;
        } catch (QueryException $e) {
            if ($this->bookingRepository->isDuplicateSlotException($e)) {
                throw new RuntimeException($this->duplicateBookingMessage(), previous: $e);
            }

            throw $e;
        }
    }

    private function calculateBookingEndTime(string $bookingDate, string $bookingTime, int $durationMinutes): string
    {
        return Carbon::createFromFormat('Y-m-d H:i', $bookingDate.' '.$bookingTime)
            ->addMinutes(max($durationMinutes, 1))
            ->format('H:i:s');
    }

    private function generateBookingCode(): string
    {
        return 'RSV-'.strtoupper((string) str()->random(8));
    }

    private function duplicateBookingMessage(): string
    {
        return '이미 예약이 접수된 시간입니다. 다른 시간을 선택해 주세요.';
    }

    private function customerCancellationBlockedMessage(BookingStatus $currentStatus): string
    {
        return match ($currentStatus) {
            BookingStatus::Cancelled => '이미 취소된 예약입니다.',
            BookingStatus::Completed => '완료된 예약은 고객이 취소할 수 없습니다.',
            BookingStatus::NoShow => '노쇼 처리된 예약은 고객이 취소할 수 없습니다.',
            default => '현재 예약 상태에서는 고객 취소가 불가능합니다.',
        };
    }

    private function canTransitionStatus(BookingStatus $currentStatus, BookingStatus $targetStatus): bool
    {
        if ($currentStatus === $targetStatus) {
            return true;
        }

        return match ($currentStatus) {
            BookingStatus::Pending => in_array($targetStatus, [BookingStatus::Confirmed, BookingStatus::Cancelled], true),
            BookingStatus::Confirmed => in_array($targetStatus, [BookingStatus::Completed, BookingStatus::Cancelled, BookingStatus::NoShow], true),
            BookingStatus::Cancelled, BookingStatus::Completed, BookingStatus::NoShow => false,
            default => false,
        };
    }

    private function invalidStatusTransitionMessage(BookingStatus $currentStatus, BookingStatus $targetStatus): string
    {
        return match (true) {
            $currentStatus === BookingStatus::Cancelled && $targetStatus === BookingStatus::Confirmed
                => '취소된 예약은 다시 확정할 수 없습니다.',
            $currentStatus === BookingStatus::Confirmed && $targetStatus === BookingStatus::Pending
                => '확정된 예약을 다시 대기 상태로 변경할 수 없습니다.',
            $currentStatus === BookingStatus::Completed
                => '완료된 예약은 더 이상 상태를 변경할 수 없습니다.',
            $currentStatus === BookingStatus::NoShow && $targetStatus !== BookingStatus::NoShow
                => '노쇼 처리된 예약은 더 이상 상태를 변경할 수 없습니다.',
            default => '요청한 예약 상태 변경을 처리할 수 없습니다.',
        };
    }

    private function runBookingStatusChangedAction(
        ReservationBooking $booking,
        BookingStatus $currentStatus,
        BookingStatus $targetStatus,
    ): void {
        $this->bookingStatusChangedAction->handle(
            $booking,
            $currentStatus->value,
            $targetStatus->value,
        );
    }

    private function runBookingCreatedNotificationAction(ReservationBooking $booking): void
    {
        $this->dispatchBookingNotificationAction->handle(
            $booking,
            'booking_created',
            null,
            BookingStatus::Pending->value,
            [
                'request_memo' => $booking->request_memo,
            ],
        );
    }

    private function runCustomerCancelledNotificationAction(
        ReservationBooking $booking,
        BookingStatus $currentStatus,
    ): void {
        $this->dispatchBookingNotificationAction->handle(
            $booking,
            'customer_cancelled',
            $currentStatus->value,
            BookingStatus::Cancelled->value,
            [
                'cancelled_by' => 'customer',
            ],
        );
    }
}
