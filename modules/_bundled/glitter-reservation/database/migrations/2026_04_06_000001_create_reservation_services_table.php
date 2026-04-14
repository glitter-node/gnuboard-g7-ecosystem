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
        Schema::create('reservation_services', function (Blueprint $table) {
            $table->id()->comment('예약 서비스 ID');
            $table->string('name', 100)->comment('서비스명');
            $table->string('slug', 100)->unique()->comment('서비스 슬러그');
            $table->text('description')->nullable()->comment('서비스 설명');
            $table->string('service_code', 30)->unique()->comment('서비스 코드');
            $table->unsignedInteger('duration_minutes')->default(60)->comment('기본 예약 소요 시간(분)');
            $table->unsignedInteger('slot_interval_minutes')->default(30)->comment('예약 슬롯 간격(분)');
            $table->unsignedInteger('buffer_before_minutes')->default(0)->comment('예약 전 준비 시간(분)');
            $table->unsignedInteger('buffer_after_minutes')->default(0)->comment('예약 후 정리 시간(분)');
            $table->unsignedInteger('capacity')->default(1)->comment('동시 예약 가능 인원 또는 팀 수');
            $table->decimal('price', 12, 2)->default(0)->comment('기본 예약 금액');
            $table->string('currency', 10)->default('KRW')->comment('통화 코드');
            $table->unsignedInteger('min_booking_days')->default(0)->comment('최소 며칠 전 예약 가능 여부');
            $table->unsignedInteger('max_booking_days')->default(90)->comment('최대 며칠 전까지 예약 가능 여부');
            $table->boolean('is_active')->default(true)->comment('서비스 활성화 여부');
            $table->unsignedInteger('sort_order')->default(0)->comment('정렬 순서');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->comment('생성자 사용자 ID');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete()->comment('수정자 사용자 ID');
            $table->timestamps();

            $table->unique(['name', 'currency'], 'reservation_services_name_currency_unique');
            $table->index(['is_active', 'sort_order'], 'reservation_services_active_sort_index');
            $table->index(['min_booking_days', 'max_booking_days'], 'reservation_services_booking_range_index');
        });

        if (DB::getDriverName() === 'mysql') {
            Schema::table('reservation_services', function (Blueprint $table) {
                $table->comment('예약 서비스 기본 정보');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservation_services');
    }
};
