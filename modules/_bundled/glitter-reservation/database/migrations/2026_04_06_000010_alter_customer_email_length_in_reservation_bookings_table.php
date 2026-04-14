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
        if (! Schema::hasColumn('reservation_bookings', 'customer_email')) {
            return;
        }

        Schema::table('reservation_bookings', function (Blueprint $table) {
            $table->string('customer_email', 255)->nullable()->comment('예약자 이메일')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('reservation_bookings', 'customer_email')) {
            return;
        }

        Schema::table('reservation_bookings', function (Blueprint $table) {
            $table->string('customer_email', 150)->nullable()->comment('예약자 이메일')->change();
        });
    }
};
