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
        Schema::create('payment_audits', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('payment_id', 36)->nullable();
            $table->string('event_type', 60);
            $table->string('checkout_request_id')->collation(config('database.default') === 'sqlite' ? 'BINARY' : 'utf8mb4_bin')->nullable();
            $table->string('callback_hash', 64)->collation(config('database.default') === 'sqlite' ? 'BINARY' : 'utf8mb4_bin')->unique();
            $table->json('payload')->nullable();
            $table->dateTime('created_at', 3)->useCurrent();
            $table->index(['payment_id', 'created_at']);
            $table->foreign('payment_id')->references('id')->on('payments')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_audits');
    }
};
