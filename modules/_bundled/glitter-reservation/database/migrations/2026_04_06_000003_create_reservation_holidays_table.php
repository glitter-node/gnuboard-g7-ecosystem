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
        Schema::create('reservation_holidays', function (Blueprint $table) {
            $table->id()->comment('예약 휴무일 ID');
            $table->foreignId('reservation_service_id')->nullable()->constrained('reservation_services')->nullOnDelete()->comment('예약 서비스 ID, null이면 공통 휴무');
            $table->date('holiday_date')->comment('휴무 일자');
            $table->string('name', 150)->comment('휴무일 명칭');
            $table->boolean('is_recurring_yearly')->default(false)->comment('매년 반복 여부');
            $table->text('notes')->nullable()->comment('휴무 메모');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->comment('생성자 사용자 ID');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete()->comment('수정자 사용자 ID');
            $table->timestamps();

            $table->unique(['reservation_service_id', 'holiday_date'], 'reservation_holidays_service_date_unique');
            $table->index(['reservation_service_id', 'holiday_date'], 'reservation_holidays_service_date_index');
            $table->index('is_recurring_yearly', 'reservation_holidays_recurring_index');
        });

        if (DB::getDriverName() === 'mysql') {
            Schema::table('reservation_holidays', function (Blueprint $table) {
                $table->comment('예약 불가 휴무일 관리');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservation_holidays');
    }
};
