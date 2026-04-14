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
        Schema::create('reservation_booking_logs', function (Blueprint $table) {
            $table->id()->comment('예약 로그 ID');
            $table->foreignId('reservation_booking_id')->constrained('reservation_bookings')->cascadeOnDelete()->comment('예약 건 ID');
            $table->string('event_type', 50)->comment('이벤트 유형');
            $table->string('from_status', 20)->nullable()->comment('변경 전 상태');
            $table->string('to_status', 20)->nullable()->comment('변경 후 상태');
            $table->text('description')->nullable()->comment('로그 설명');
            $table->json('payload')->nullable()->comment('부가 데이터');
            $table->foreignId('logged_by')->nullable()->constrained('users')->nullOnDelete()->comment('로그 기록 사용자 ID');
            $table->timestamps();

            $table->unique(
                ['reservation_booking_id', 'event_type', 'created_at'],
                'reservation_booking_logs_booking_event_created_unique'
            );
            $table->index(['reservation_booking_id', 'created_at'], 'reservation_booking_logs_booking_created_index');
            $table->index('event_type', 'reservation_booking_logs_event_type_index');
            $table->index(['from_status', 'to_status'], 'reservation_booking_logs_status_change_index');
        });

        if (DB::getDriverName() === 'mysql') {
            Schema::table('reservation_booking_logs', function (Blueprint $table) {
                $table->comment('예약 상태 변경 및 이력 로그');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservation_booking_logs');
    }
};
