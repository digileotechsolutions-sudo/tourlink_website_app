<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('account_sessions')) {
            return;
        }

        Schema::create('account_sessions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('user_id', 36)->unique();
            $table->string('session_fingerprint', 64);
            $table->string('session_type', 16);
            $table->unsignedBigInteger('personal_access_token_id')->nullable()->unique();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('last_activity_at');
            $table->timestamp('expires_at')->index();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_sessions');
    }
};
