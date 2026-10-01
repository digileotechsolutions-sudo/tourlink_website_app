<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('verification_requests', function (Blueprint $table): void {
            $table->string('vehicle_id', 36)->nullable()->after('user_id');
            $table->foreign('vehicle_id')->references('id')->on('vehicles')->nullOnDelete();
            $table->index(['user_id', 'type', 'vehicle_id'], 'verification_requests_vehicle_scope_idx');
        });
    }

    public function down(): void
    {
        Schema::table('verification_requests', function (Blueprint $table): void {
            $table->dropIndex('verification_requests_vehicle_scope_idx');
            $table->dropForeign(['vehicle_id']);
            $table->dropColumn('vehicle_id');
        });
    }
};
