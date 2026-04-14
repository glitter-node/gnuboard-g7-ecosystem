<?php

namespace Modules\Glitter\Reservation\Tests\Feature;

require_once __DIR__.'/../ModuleTestCase.php';

use Illuminate\Support\Facades\Validator;
use Modules\Glitter\Reservation\Http\Requests\SlotAvailabilityRequest;
use Modules\Glitter\Reservation\Http\Requests\StoreReservationRequest;
use Modules\Glitter\Reservation\Models\ReservationService;
use Modules\Glitter\Reservation\Tests\ModuleTestCase;

class ReservationRequestTest extends ModuleTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        ReservationService::query()->firstOrCreate(
            ['slug' => 'default-service'],
            [
                'name' => '기본 상담',
                'duration_minutes' => 60,
                'slot_interval_minutes' => 30,
                'capacity' => 1,
                'price' => 0,
                'currency' => 'KRW',
                'min_booking_days' => 0,
                'max_booking_days' => 90,
                'is_active' => true,
                'sort_order' => 0,
            ]
        );
    }

    public function test_requests_authorize_returns_true(): void
    {
        $this->assertTrue((new StoreReservationRequest())->authorize());
        $this->assertTrue((new SlotAvailabilityRequest())->authorize());
    }

    public function test_store_request_passes_with_valid_data(): void
    {
        $request = new StoreReservationRequest();
        $validator = Validator::make($this->validStoreData(), $request->rules());

        $this->assertTrue($validator->passes());
    }

    public function test_store_request_fails_when_required_fields_missing(): void
    {
        $request = new StoreReservationRequest();
        $validator = Validator::make([], $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('service_id', $validator->errors()->toArray());
        $this->assertArrayHasKey('booking_date', $validator->errors()->toArray());
        $this->assertArrayHasKey('booking_time', $validator->errors()->toArray());
        $this->assertArrayHasKey('customer_name', $validator->errors()->toArray());
        $this->assertArrayHasKey('customer_phone', $validator->errors()->toArray());
    }

    public function test_store_request_rejects_past_booking_date(): void
    {
        $request = new StoreReservationRequest();
        $data = $this->validStoreData();
        $data['booking_date'] = now()->subDay()->toDateString();

        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('booking_date', $validator->errors()->toArray());
    }

    public function test_slot_request_passes_with_valid_data(): void
    {
        $request = new SlotAvailabilityRequest();
        $validator = Validator::make($this->validSlotData(), $request->rules());

        $this->assertTrue($validator->passes());
    }

    public function test_slot_request_requires_service_and_future_date(): void
    {
        $request = new SlotAvailabilityRequest();
        $validator = Validator::make([
            'service_id' => null,
            'booking_date' => now()->subDay()->toDateString(),
        ], $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('service_id', $validator->errors()->toArray());
        $this->assertArrayHasKey('booking_date', $validator->errors()->toArray());
    }

    /**
     * @return array<string, mixed>
     */
    private function validStoreData(): array
    {
        return [
            'service_id' => $this->serviceId(),
            'booking_date' => now()->addDay()->toDateString(),
            'booking_time' => '14:00',
            'customer_name' => '홍길동',
            'customer_phone' => '01012345678',
            'customer_email' => 'hong@example.com',
            'student_grade' => '초등 5학년',
            'memo' => '첫 상담 요청',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validSlotData(): array
    {
        return [
            'service_id' => $this->serviceId(),
            'booking_date' => now()->addDay()->toDateString(),
        ];
    }

    private function serviceId(): int
    {
        return (int) ReservationService::query()->value('id');
    }
}
