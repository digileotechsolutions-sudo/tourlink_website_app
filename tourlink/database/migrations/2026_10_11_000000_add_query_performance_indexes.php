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
        Schema::table('trips', function (Blueprint $table): void {
            $table->index(['status', 'verification_status', 'featured', 'created_at'], 'trips_public_listing_idx');
            $table->index(['operator_id', 'verification_status'], 'trips_operator_verification_idx');
            $table->index('category_id', 'trips_category_id_idx');
        });

        Schema::table('vehicles', function (Blueprint $table): void {
            $table->index(['status', 'verification_status', 'created_at'], 'vehicles_public_listing_idx');
            $table->index(['owner_id', 'verification_status'], 'vehicles_owner_verification_idx');
        });

        Schema::table('bookings', function (Blueprint $table): void {
            $table->index(['traveler_id', 'start_date'], 'bookings_traveler_start_date_idx');
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->index(['booking_id', 'created_at'], 'payments_booking_created_at_idx');
        });

        Schema::table('reviews', function (Blueprint $table): void {
            $table->index(['trip_id', 'rating'], 'reviews_trip_rating_idx');
            $table->index(['vehicle_id', 'rating'], 'reviews_vehicle_rating_idx');
        });

        Schema::table('verification_requests', function (Blueprint $table): void {
            $table->index(['status', 'created_at'], 'verification_status_created_at_idx');
        });

        Schema::table('admin_logs', function (Blueprint $table): void {
            $table->index('created_at', 'admin_logs_created_at_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admin_logs', function (Blueprint $table): void {
            $table->dropIndex('admin_logs_created_at_idx');
        });

        Schema::table('verification_requests', function (Blueprint $table): void {
            $table->dropIndex('verification_status_created_at_idx');
        });

        Schema::table('reviews', function (Blueprint $table): void {
            $table->dropIndex('reviews_trip_rating_idx');
            $table->dropIndex('reviews_vehicle_rating_idx');
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->dropIndex('payments_booking_created_at_idx');
        });

        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropIndex('bookings_traveler_start_date_idx');
        });

        Schema::table('vehicles', function (Blueprint $table): void {
            $table->dropIndex('vehicles_public_listing_idx');
            $table->dropIndex('vehicles_owner_verification_idx');
        });

        Schema::table('trips', function (Blueprint $table): void {
            $table->dropIndex('trips_public_listing_idx');
            $table->dropIndex('trips_operator_verification_idx');
            $table->dropIndex('trips_category_id_idx');
        });
    }
};
