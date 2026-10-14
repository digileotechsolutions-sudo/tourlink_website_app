<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_owner_profiles', function (Blueprint $table): void {
            $table->text('logo_url')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_owner_profiles', function (Blueprint $table): void {
            $table->dropColumn('logo_url');
        });
    }
};
