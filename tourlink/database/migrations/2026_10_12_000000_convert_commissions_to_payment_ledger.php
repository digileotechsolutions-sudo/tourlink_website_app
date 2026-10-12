<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commissions', function (Blueprint $table): void {
            $table->dropUnique('commissions_booking_id_unique');
            $table->string('event_type', 30)->default('LEGACY');
            $table->string('payment_id', 36)->nullable()->index();
            $table->string('refund_id', 36)->nullable()->unique();
            $table->string('event_key', 80)->nullable()->unique();
            $table->foreign('payment_id')->references('id')->on('payments')->nullOnDelete();
            $table->foreign('refund_id')->references('id')->on('refunds')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('commissions', function (Blueprint $table): void {
            $table->dropForeign(['payment_id']);
            $table->dropForeign(['refund_id']);
            $table->dropIndex(['payment_id']);
            $table->dropUnique(['refund_id']);
            $table->dropUnique(['event_key']);
            $table->dropColumn(['event_type', 'payment_id', 'refund_id', 'event_key']);
            $table->unique('booking_id');
        });
    }
};
