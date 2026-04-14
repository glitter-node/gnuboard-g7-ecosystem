<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const AUTO_NOTE = '[auto-backfill-weekdays]';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('reservation_services') || ! Schema::hasTable('reservation_schedules')) {
            return;
        }

        $now = now();
        $targetWeekdays = [1, 2, 3, 4, 5];

        $services = DB::table('reservation_services')
            ->where('is_active', true)
            ->get(['id']);

        foreach ($services as $service) {
            $weeklySchedules = DB::table('reservation_schedules')
                ->where('reservation_service_id', $service->id)
                ->whereNull('specific_date')
                ->where('is_active', true)
                ->orderBy('day_of_week')
                ->get();

            if ($weeklySchedules->count() !== 1) {
                continue;
            }

            $baseSchedule = $weeklySchedules->first();

            if ($baseSchedule === null || $baseSchedule->day_of_week === null) {
                continue;
            }

            $existingWeekdays = $weeklySchedules
                ->pluck('day_of_week')
                ->filter(static fn ($value): bool => $value !== null)
                ->map(static fn ($value): int => (int) $value)
                ->all();

            $missingWeekdays = array_values(array_diff($targetWeekdays, $existingWeekdays));

            foreach ($missingWeekdays as $weekday) {
                DB::table('reservation_schedules')->insert([
                    'reservation_service_id' => $service->id,
                    'day_of_week' => $weekday,
                    'specific_date' => null,
                    'start_time' => $baseSchedule->start_time,
                    'end_time' => $baseSchedule->end_time,
                    'break_start_time' => $baseSchedule->break_start_time,
                    'break_end_time' => $baseSchedule->break_end_time,
                    'slot_capacity' => $baseSchedule->slot_capacity,
                    'is_active' => true,
                    'notes' => trim(((string) ($baseSchedule->notes ?? '')).' '.self::AUTO_NOTE),
                    'created_by' => $baseSchedule->created_by,
                    'updated_by' => $baseSchedule->updated_by,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('reservation_schedules')) {
            return;
        }

        DB::table('reservation_schedules')
            ->whereIn('day_of_week', [1, 3, 4, 5])
            ->where('notes', 'like', '%'.self::AUTO_NOTE.'%')
            ->delete();
    }
};
