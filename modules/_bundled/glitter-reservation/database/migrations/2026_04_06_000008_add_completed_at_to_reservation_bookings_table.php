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
        if (! Schema::hasColumn('reservation_bookings', 'completed_at')) {
            Schema::table('reservation_bookings', function (Blueprint $table) {
                $table->timestamp('completed_at')->nullable()->after('cancelled_at')->comment('예약 완료 일시');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('reservation_bookings', 'completed_at')) {
            Schema::table('reservation_bookings', function (Blueprint $table) {
                $table->dropColumn('completed_at');
            });
        }
    }
};
