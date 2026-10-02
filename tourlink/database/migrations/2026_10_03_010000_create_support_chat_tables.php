<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_conversations', function (Blueprint $table): void {
            $table->string('id', 36)->primary();
            $table->string('user_id', 36);
            $table->string('assigned_admin_id', 36)->nullable();
            $table->string('booking_id', 36)->nullable();
            $table->string('status', 16)->default('open');
            $table->dateTime('last_message_at', 3)->nullable();
            $table->dateTime('last_read_by_customer_at', 3)->nullable();
            $table->dateTime('last_read_by_admin_at', 3)->nullable();
            $table->timestamps(3);
            $table->index(['user_id', 'status']);
            $table->index(['status', 'last_message_at']);
            $table->index(['assigned_admin_id', 'status']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('assigned_admin_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('booking_id')->references('id')->on('bookings')->nullOnDelete();
        });

        Schema::create('support_messages', function (Blueprint $table): void {
            $table->string('id', 36)->primary();
            $table->string('conversation_id', 36);
            $table->string('sender_id', 36);
            $table->enum('sender_type', ['customer', 'admin']);
            $table->text('message');
            $table->dateTime('read_at', 3)->nullable();
            $table->dateTime('created_at', 3)->useCurrent();
            $table->dateTime('updated_at', 3)->nullable();
            $table->index(['conversation_id', 'created_at']);
            $table->index(['sender_id', 'created_at']);
            $table->index(['conversation_id', 'sender_type', 'read_at']);
            $table->foreign('conversation_id')->references('id')->on('support_conversations')->cascadeOnDelete();
            $table->foreign('sender_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_messages');
        Schema::dropIfExists('support_conversations');
    }
};
