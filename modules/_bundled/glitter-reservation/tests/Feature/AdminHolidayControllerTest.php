<?php

namespace Modules\Glitter\Reservation\Tests\Feature;

require_once __DIR__.'/../ModuleTestCase.php';

use App\Models\User;
use Modules\Glitter\Reservation\Models\ReservationHoliday;
use Modules\Glitter\Reservation\Tests\ModuleTestCase;

class AdminHolidayControllerTest extends ModuleTestCase
{
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = $this->createAdminUser([
            'reservation.reservations.read',
            'reservation.reservations.create',
            'reservation.reservations.update',
            'reservation.reservations.delete',
        ]);
    }

    public function test_admin_can_list_holidays(): void
    {
        $holiday = ReservationHoliday::query()->create([
            'reservation_service_id' => null,
            'holiday_date' => now()->addDays(7)->toDateString(),
            'name' => '정기 휴무',
            'is_recurring_yearly' => false,
            'notes' => '목록 테스트',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/modules/glitter-reservation/admin/reservation/holidays');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $holiday->id)
            ->assertJsonPath('data.0.holiday_date', $holiday->holiday_date->format('Y-m-d'));
    }

    public function test_admin_can_create_holiday(): void
    {
        $holidayDate = now()->addDays(10)->toDateString();

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/modules/glitter-reservation/admin/reservation/holidays', [
                'holiday_date' => $holidayDate,
                'name' => '임시 휴무',
                'is_recurring_yearly' => false,
                'notes' => '생성 테스트',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.holiday_date', $holidayDate)
            ->assertJsonPath('data.name', '임시 휴무');

        $this->assertDatabaseHas('reservation_holidays', [
            'holiday_date' => $holidayDate,
            'name' => '임시 휴무',
            'reservation_service_id' => null,
        ]);
    }

    public function test_admin_cannot_create_duplicate_holiday_on_same_date(): void
    {
        $holidayDate = now()->addDays(14)->toDateString();

        ReservationHoliday::query()->create([
            'reservation_service_id' => null,
            'holiday_date' => $holidayDate,
            'name' => '기존 휴무',
            'is_recurring_yearly' => false,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/modules/glitter-reservation/admin/reservation/holidays', [
                'holiday_date' => $holidayDate,
                'name' => '중복 휴무',
                'is_recurring_yearly' => false,
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['success', 'message']);
    }

    public function test_admin_can_update_holiday(): void
    {
        $holiday = ReservationHoliday::query()->create([
            'reservation_service_id' => null,
            'holiday_date' => now()->addDays(20)->toDateString(),
            'name' => '수정 전 휴무',
            'is_recurring_yearly' => false,
            'notes' => '수정 전',
        ]);

        $newDate = now()->addDays(21)->toDateString();

        $response = $this->actingAs($this->adminUser)
            ->putJson("/api/modules/glitter-reservation/admin/reservation/holidays/{$holiday->id}", [
                'holiday_date' => $newDate,
                'name' => '수정 후 휴무',
                'is_recurring_yearly' => true,
                'notes' => '수정 후',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $holiday->id)
            ->assertJsonPath('data.holiday_date', $newDate)
            ->assertJsonPath('data.name', '수정 후 휴무')
            ->assertJsonPath('data.is_recurring_yearly', true);
    }

    public function test_admin_can_delete_holiday(): void
    {
        $holiday = ReservationHoliday::query()->create([
            'reservation_service_id' => null,
            'holiday_date' => now()->addDays(30)->toDateString(),
            'name' => '삭제 대상 휴무',
            'is_recurring_yearly' => false,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->deleteJson("/api/modules/glitter-reservation/admin/reservation/holidays/{$holiday->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'message']);

        $this->assertDatabaseMissing('reservation_holidays', [
            'id' => $holiday->id,
        ]);
    }
}
