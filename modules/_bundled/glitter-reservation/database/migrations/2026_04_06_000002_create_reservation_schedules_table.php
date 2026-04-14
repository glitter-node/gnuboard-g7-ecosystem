<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reservation_schedules', function (Blueprint $table) {
            $table->id()->comment('예약 스케줄 ID');
            $table->foreignId('reservation_service_id')->constrained('reservation_services')->cascadeOnDelete()->comment('예약 서비스 ID');
            $table->unsignedTinyInteger('day_of_week')->nullable()->comment('요일 번호(0:일요일~6:토요일)');
            $table->date('specific_date')->nullable()->comment('특정 일자 운영 스케줄');
            $table->time('start_time')->comment('운영 시작 시간');
            $table->time('end_time')->comment('운영 종료 시간');
            $table->unsignedInteger('slot_capacity')->default(1)->comment('슬롯별 허용 예약 수');
            $table->boolean('is_active')->default(true)->comment('스케줄 활성화 여부');
            $table->text('notes')->nullable()->comment('운영 메모');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->comment('생성자 사용자 ID');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete()->comment('수정자 사용자 ID');
            $table->timestamps();

            $table->index(['reservation_service_id', 'day_of_week'], 'reservation_schedules_service_day_index');
            $table->index(['reservation_service_id', 'specific_date'], 'reservation_schedules_service_date_index');
            $table->unique(
                ['reservation_service_id', 'day_of_week', 'start_time', 'end_time'],
                'reservation_schedules_service_day_time_unique'
            );
            $table->index('is_active', 'reservation_schedules_is_active_index');
        });

        if (DB::getDriverName() === 'mysql') {
            Schema::table('reservation_schedules', function (Blueprint $table) {
                $table->comment('예약 가능 운영 시간표');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservation_schedules');
    }
};
