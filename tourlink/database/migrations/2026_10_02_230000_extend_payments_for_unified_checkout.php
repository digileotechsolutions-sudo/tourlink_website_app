<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->string('payment_method', 32)->default('MPESA');
            $table->string('receipt_number', 50)->nullable()->unique();
            $table->string('received_by', 36)->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->foreign('received_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['payment_method', 'status', 'paid_at'], 'payments_method_status_paid_at_index');
        });

        Schema::table('refunds', function (Blueprint $table): void {
            $table->string('status', 32)->default('COMPLETED');
            $table->string('reference', 50)->nullable()->unique();
            $table->string('provider_reference')->nullable();
            $table->string('authorized_by', 36)->nullable();
            $table->json('metadata')->nullable();
            $table->foreign('authorized_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['status', 'created_at'], 'refunds_status_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('refunds', function (Blueprint $table): void {
            $table->dropForeign(['authorized_by']);
            $table->dropIndex('refunds_status_created_at_index');
            $table->dropUnique(['reference']);
            $table->dropColumn(['status', 'reference', 'provider_reference', 'authorized_by', 'metadata']);
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->dropForeign(['received_by']);
            $table->dropIndex('payments_method_status_paid_at_index');
            $table->dropUnique(['receipt_number']);
            $table->dropColumn(['payment_method', 'receipt_number', 'received_by', 'notes', 'metadata']);
        });
    }
};
