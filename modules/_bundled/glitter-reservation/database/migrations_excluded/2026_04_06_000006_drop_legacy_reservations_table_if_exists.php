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
        if (Schema::hasTable('reservations')) {
            Schema::drop('reservations');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('reservations')) {
            return;
        }

        Schema::create('reservations', function (Blueprint $table) {
            $table->id()->comment('레거시 예약 ID');
            $table->string('reservation_code', 30)->unique()->comment('레거시 예약 코드');
            $table->string('name', 100)->comment('예약자 이름');
            $table->string('phone', 30)->comment('예약자 연락처');
            $table->string('email', 150)->nullable()->comment('예약자 이메일');
            $table->date('reservation_date')->comment('예약 일자');
            $table->time('reservation_time')->comment('예약 시간');
            $table->unsignedInteger('guest_count')->default(1)->comment('예약 인원 수');
            $table->string('status', 20)->default('pending')->comment('예약 상태');
            $table->text('notes')->nullable()->comment('관리자 메모');
            $table->unsignedBigInteger('created_by')->nullable()->comment('생성자 사용자 ID');
            $table->unsignedBigInteger('updated_by')->nullable()->comment('수정자 사용자 ID');
            $table->timestamps();

            $table->index(['reservation_date', 'reservation_time'], 'reservations_date_time_index');
            $table->index('status', 'reservations_status_index');
        });

        if (DB::getDriverName() === 'mysql') {
            Schema::table('reservations', function (Blueprint $table) {
                $table->comment('레거시 예약 단일 테이블');
            });
        }
    }
};
