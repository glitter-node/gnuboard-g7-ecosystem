<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_email_verifications', function (Blueprint $table) {
            $table->id()->comment('예약 이메일 인증 요청 ID');
            $table->string('email', 150)->comment('인증 요청 이메일');
            $table->string('token_hash', 64)->unique()->comment('인증 토큰 해시');
            $table->timestamp('expires_at')->comment('인증 토큰 만료 일시');
            $table->timestamp('verified_at')->nullable()->comment('인증 완료 일시');
            $table->timestamp('used_at')->nullable()->comment('인증 토큰 사용 일시');
            $table->string('ip', 45)->nullable()->comment('요청 IP');
            $table->text('user_agent')->nullable()->comment('요청 User Agent');
            $table->timestamps();

            $table->index(['email', 'created_at'], 'reservation_email_verifications_email_created_index');
            $table->index(['expires_at', 'used_at'], 'reservation_email_verifications_expires_used_index');
        });

        if (DB::getDriverName() === 'mysql') {
            Schema::table('reservation_email_verifications', function (Blueprint $table) {
                $table->comment('예약 진행 전 이메일 링크 인증 이력');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_email_verifications');
    }
};
