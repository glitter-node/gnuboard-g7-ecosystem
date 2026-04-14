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
        Schema::table('reservation_bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('reservation_bookings', 'student_grade')) {
                $table->string('student_grade', 50)
                    ->nullable()
                    ->after('customer_phone')
                    ->comment('학생 학년');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservation_bookings', function (Blueprint $table) {
            if (Schema::hasColumn('reservation_bookings', 'student_grade')) {
                $table->dropColumn('student_grade');
            }
        });
    }
};
