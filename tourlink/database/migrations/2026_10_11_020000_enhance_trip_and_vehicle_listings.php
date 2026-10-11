<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table): void {
            $table->string('currency', 3)->default('KES');
            $table->unsignedTinyInteger('group_discount_percent')->nullable();
        });

        Schema::table('vehicles', function (Blueprint $table): void {
            $table->string('currency', 3)->default('KES');
            $table->unsignedInteger('hourly_price')->nullable();
            $table->unsignedInteger('trip_price')->nullable();
            $table->unsignedInteger('driver_charge')->nullable();
            $table->json('additional_charges')->nullable();
            $table->json('service_areas')->nullable();
            $table->dateTime('submitted_at', 3)->nullable();
        });

        DB::table('vehicles')
            ->where('verification_status', 'PENDING')
            ->update(['submitted_at' => DB::raw('created_at')]);

        Schema::table('vehicle_images', function (Blueprint $table): void {
            $table->unsignedInteger('sort_order')->default(0);
            $table->index(['vehicle_id', 'sort_order'], 'vehicle_images_order_idx');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_images', function (Blueprint $table): void {
            $table->dropIndex('vehicle_images_order_idx');
            $table->dropColumn('sort_order');
        });

        Schema::table('vehicles', function (Blueprint $table): void {
            $table->dropColumn(['currency', 'hourly_price', 'trip_price', 'driver_charge', 'additional_charges', 'service_areas', 'submitted_at']);
        });

        Schema::table('trips', function (Blueprint $table): void {
            $table->dropColumn(['currency', 'group_discount_percent']);
        });
    }
};
