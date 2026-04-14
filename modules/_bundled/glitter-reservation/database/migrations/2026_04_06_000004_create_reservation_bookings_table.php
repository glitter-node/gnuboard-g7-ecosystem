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
        Schema::create('reservation_bookings', function (Blueprint $table) {
            $table->id()->comment('예약 건 ID');
            $table->foreignId('reservation_service_id')->constrained('reservation_services')->restrictOnDelete()->comment('예약 서비스 ID');
            $table->foreignId('reservation_schedule_id')->nullable()->constrained('reservation_schedules')->nullOnDelete()->comment('예약 스케줄 ID');
            $table->string('booking_code', 30)->unique()->comment('예약 코드');
            $table->string('customer_name', 100)->comment('예약자 이름');
            $table->string('customer_phone', 30)->comment('예약자 연락처');
            $table->string('customer_email', 150)->nullable()->comment('예약자 이메일');
            $table->date('booking_date')->comment('예약 일자');
            $table->time('booking_time')->comment('예약 시작 시간');
            $table->time('booking_end_time')->nullable()->comment('예약 종료 시간');
            $table->unsignedInteger('guest_count')->default(1)->comment('예약 인원 수');
            $table->string('status', 20)->default('pending')->comment('예약 상태');
            $table->text('request_memo')->nullable()->comment('예약 요청 메모');
            $table->text('admin_memo')->nullable()->comment('관리자 메모');
            $table->timestamp('confirmed_at')->nullable()->comment('예약 확정 일시');
            $table->timestamp('cancelled_at')->nullable()->comment('예약 취소 일시');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->comment('생성자 사용자 ID');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete()->comment('수정자 사용자 ID');
            $table->timestamps();

            $table->unique(
                ['reservation_service_id', 'booking_date', 'booking_time'],
                'reservation_bookings_service_date_time_unique'
            );
            $table->index(['reservation_service_id', 'booking_date', 'booking_time'], 'reservation_bookings_service_date_time_index');
            $table->index(['status', 'booking_date'], 'reservation_bookings_status_date_index');
            $table->index('customer_phone', 'reservation_bookings_customer_phone_index');
            $table->index(['customer_email', 'booking_date'], 'reservation_bookings_customer_email_date_index');
        });

        if (DB::getDriverName() === 'mysql') {
            Schema::table('reservation_bookings', function (Blueprint $table) {
                $table->comment('예약 접수 및 상태 관리');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservation_bookings');
    }
};
