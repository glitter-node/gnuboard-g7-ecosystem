<?php

namespace Modules\Glitter\Reservation\Http\Controllers\Admin;

use App\Http\Controllers\Api\Base\AdminBaseController;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Modules\Glitter\Reservation\Http\Requests\AdminHolidayStoreRequest;
use Modules\Glitter\Reservation\Http\Requests\AdminHolidayUpdateRequest;
use Modules\Glitter\Reservation\Http\Resources\HolidayResource;
use Modules\Glitter\Reservation\Services\HolidayService;
use RuntimeException;

class HolidayController extends AdminBaseController
{
    public function __construct(private HolidayService $holidayService)
    {
        parent::__construct();
    }

    public function index(): JsonResponse
    {
        try {
            return $this->successWithResource(
                'common.success',
                HolidayResource::collection($this->holidayService->getCommonHolidays())
            );
        } catch (\Throwable $e) {
            return $this->error('휴무일 목록을 불러오지 못했습니다.', 500, $e->getMessage());
        }
    }

    public function store(AdminHolidayStoreRequest $request): JsonResponse
    {
        try {
            return $this->successWithResource(
                'common.success',
                new HolidayResource(
                    $this->holidayService->createCommonHoliday(
                        $request->validated(),
                        $this->getCurrentUser()?->getKey(),
                    )
                ),
                201
            );
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (\Throwable $e) {
            return $this->error('휴무일을 등록하지 못했습니다.', 500, $e->getMessage());
        }
    }

    public function update(AdminHolidayUpdateRequest $request, int $holidayId): JsonResponse
    {
        try {
            return $this->successWithResource(
                'common.success',
                new HolidayResource(
                    $this->holidayService->updateCommonHoliday(
                        $holidayId,
                        $request->validated(),
                        $this->getCurrentUser()?->getKey(),
                    )
                )
            );
        } catch (ModelNotFoundException) {
            return $this->notFound('휴무일을 찾을 수 없습니다.');
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (\Throwable $e) {
            return $this->error('휴무일을 수정하지 못했습니다.', 500, $e->getMessage());
        }
    }

    public function destroy(int $holidayId): JsonResponse
    {
        try {
            $this->holidayService->deleteCommonHoliday($holidayId);

            return $this->success('common.success');
        } catch (ModelNotFoundException) {
            return $this->notFound('휴무일을 찾을 수 없습니다.');
        } catch (\Throwable $e) {
            return $this->error('휴무일을 삭제하지 못했습니다.', 500, $e->getMessage());
        }
    }
}
