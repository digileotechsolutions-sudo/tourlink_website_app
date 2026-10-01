<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('verification_requests', function (Blueprint $table): void {
            $table->string('status', 32)->default('PENDING')->change();
        });
    }

    public function down(): void
    {
        DB::table('verification_requests')
            ->where('status', 'UNDER_REVIEW')
            ->update(['status' => 'PENDING']);

        Schema::table('verification_requests', function (Blueprint $table): void {
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED', 'MORE_INFO'])->default('PENDING')->change();
        });
    }
};
