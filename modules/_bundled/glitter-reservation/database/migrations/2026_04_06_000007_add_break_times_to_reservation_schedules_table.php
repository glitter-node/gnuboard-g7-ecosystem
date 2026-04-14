<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reservation_schedules', function (Blueprint $table) {
            if (! Schema::hasColumn('reservation_schedules', 'break_start_time')) {
                $table->time('break_start_time')->nullable()->after('end_time')->comment('운영 중 휴식 시작 시간');
            }

            if (! Schema::hasColumn('reservation_schedules', 'break_end_time')) {
                $table->time('break_end_time')->nullable()->after('break_start_time')->comment('운영 중 휴식 종료 시간');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservation_schedules', function (Blueprint $table) {
            if (Schema::hasColumn('reservation_schedules', 'break_end_time')) {
                $table->dropColumn('break_end_time');
            }

            if (Schema::hasColumn('reservation_schedules', 'break_start_time')) {
                $table->dropColumn('break_start_time');
            }
        });
    }
};
