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
        Schema::create('traveler_profiles', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('user_id', 36)->unique();
            $table->text('bio')->nullable();
            $table->string('preferred_currency', 3)->default('KES');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('operator_profiles', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('user_id', 36)->unique();
            $table->string('company_name');
            $table->string('slug')->collation(config('database.default') === 'sqlite' ? 'BINARY' : 'utf8mb4_bin')->unique();
            $table->text('description')->nullable();
            $table->text('logo_url')->nullable();
            $table->text('website')->nullable();
            $table->integer('years_active')->default(1);
            $table->integer('response_rate')->default(95);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('vehicle_owner_profiles', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('user_id', 36)->unique();
            $table->string('business_name')->nullable();
            $table->text('description')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('otp_challenges', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('user_id', 36);
            $table->enum('channel', ['EMAIL', 'PHONE']);
            $table->string('code_hash', 64);
            $table->dateTime('expires_at', 3);
            $table->integer('attempts')->default(0);
            $table->dateTime('consumed_at', 3)->nullable();
            $table->dateTime('created_at', 3)->useCurrent();
            $table->index(['user_id', 'channel', 'created_at']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('destinations', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('name');
            $table->string('slug')->collation(config('database.default') === 'sqlite' ? 'BINARY' : 'utf8mb4_bin')->unique();
            $table->string('country')->default('Kenya');
            $table->text('description');
            $table->text('image_url');
            $table->string('location')->nullable();
            $table->json('attractions');
            $table->json('activities');
            $table->index('country');
        });

        Schema::create('trip_categories', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('name');
            $table->string('slug')->collation(config('database.default') === 'sqlite' ? 'BINARY' : 'utf8mb4_bin')->unique();
        });

        Schema::create('vehicles', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('slug')->collation(config('database.default') === 'sqlite' ? 'BINARY' : 'utf8mb4_bin')->unique();
            $table->string('name');
            $table->string('registration_number', 30);
            $table->string('make');
            $table->string('model');
            $table->integer('year');
            $table->string('body_type');
            $table->integer('engine_cc')->nullable();
            $table->string('colour')->nullable();
            $table->integer('seating_capacity');
            $table->integer('tare_weight')->nullable();
            $table->integer('axles')->nullable();
            $table->integer('load_capacity')->nullable();
            $table->string('transmission');
            $table->string('fuel_type');
            $table->boolean('air_conditioning')->default(false);
            $table->boolean('four_by_four')->default(false);
            $table->boolean('driver_included')->default(false);
            $table->integer('price_per_day');
            $table->string('location');
            $table->enum('status', ['DRAFT', 'PUBLISHED', 'ARCHIVED'])->default('PUBLISHED');
            $table->enum('verification_status', ['PENDING', 'APPROVED', 'REJECTED', 'MORE_INFO'])->default('APPROVED');
            $table->string('owner_id', 36);
            $table->string('destination_id', 36)->nullable();
            $table->dateTime('created_at', 3)->useCurrent();
            $table->index(['location', 'status']);
            $table->foreign('owner_id')->references('id')->on('users');
            $table->foreign('destination_id')->references('id')->on('destinations')->nullOnDelete();
        });

        Schema::create('vehicle_images', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->text('url');
            $table->string('alt')->nullable();
            $table->string('vehicle_id', 36);
            $table->foreign('vehicle_id')->references('id')->on('vehicles')->cascadeOnDelete();
        });

        Schema::create('vehicle_availabilities', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('vehicle_id', 36);
            $table->dateTime('date', 3);
            $table->boolean('available')->default(true);
            $table->unique(['vehicle_id', 'date']);
            $table->foreign('vehicle_id')->references('id')->on('vehicles')->cascadeOnDelete();
        });

        Schema::create('trips', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('slug')->collation(config('database.default') === 'sqlite' ? 'BINARY' : 'utf8mb4_bin')->unique();
            $table->string('name');
            $table->text('description');
            $table->string('starting_point');
            $table->string('ending_point');
            $table->integer('duration_days');
            $table->dateTime('departure_date', 3);
            $table->dateTime('return_date', 3);
            $table->integer('price_per_person');
            $table->integer('max_travelers');
            $table->integer('min_travelers')->default(1);
            $table->integer('available_seats');
            $table->json('pickup_points');
            $table->json('itinerary');
            $table->text('accommodation')->nullable();
            $table->json('meals');
            $table->text('transport')->nullable();
            $table->json('activities');
            $table->json('included_items');
            $table->json('excluded_items');
            $table->text('cancellation_policy');
            $table->enum('status', ['DRAFT', 'PUBLISHED', 'ARCHIVED'])->default('PUBLISHED');
            $table->boolean('featured')->default(false);
            $table->enum('verification_status', ['PENDING', 'APPROVED', 'REJECTED', 'MORE_INFO'])->default('APPROVED');
            $table->string('operator_id', 36);
            $table->string('destination_id', 36);
            $table->string('category_id', 36);
            $table->string('required_vehicle_id', 36)->nullable();
            $table->dateTime('created_at', 3)->useCurrent();
            $table->dateTime('updated_at', 3);
            $table->index(['destination_id', 'status']);
            $table->index('featured');
            $table->foreign('operator_id')->references('id')->on('users');
            $table->foreign('destination_id')->references('id')->on('destinations');
            $table->foreign('category_id')->references('id')->on('trip_categories');
            $table->foreign('required_vehicle_id')->references('id')->on('vehicles')->nullOnDelete();
        });

        Schema::create('trip_images', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->text('url');
            $table->string('alt')->nullable();
            $table->integer('sort_order')->default(0);
            $table->string('trip_id', 36);
            $table->foreign('trip_id')->references('id')->on('trips')->cascadeOnDelete();
        });

        Schema::create('trip_availabilities', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('trip_id', 36);
            $table->dateTime('date', 3);
            $table->integer('seats');
            $table->unique(['trip_id', 'date']);
            $table->foreign('trip_id')->references('id')->on('trips')->cascadeOnDelete();
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('reference')->collation(config('database.default') === 'sqlite' ? 'BINARY' : 'utf8mb4_bin')->unique();
            $table->enum('type', ['TRIP', 'VEHICLE']);
            $table->enum('status', ['PENDING', 'CONFIRMED', 'PAID', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED', 'REFUNDED'])->default('PENDING');
            $table->string('traveler_id', 36);
            $table->string('trip_id', 36)->nullable();
            $table->string('vehicle_id', 36)->nullable();
            $table->dateTime('start_date', 3);
            $table->dateTime('end_date', 3);
            $table->integer('travelers')->default(1);
            $table->string('pickup_location')->nullable();
            $table->boolean('driver_required')->default(false);
            $table->integer('base_amount');
            $table->integer('fees')->default(0);
            $table->integer('discount')->default(0);
            $table->integer('total_amount');
            $table->string('currency', 3)->default('KES');
            $table->text('notes')->nullable();
            $table->dateTime('created_at', 3)->useCurrent();
            $table->dateTime('updated_at', 3);
            $table->index(['traveler_id', 'status']);
            $table->index(['trip_id', 'start_date', 'end_date']);
            $table->index(['vehicle_id', 'start_date', 'end_date']);
            $table->foreign('traveler_id')->references('id')->on('users');
            $table->foreign('trip_id')->references('id')->on('trips');
            $table->foreign('vehicle_id')->references('id')->on('vehicles');
        });

        Schema::create('booking_passengers', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('booking_id', 36);
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->foreign('booking_id')->references('id')->on('bookings')->cascadeOnDelete();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('booking_id', 36);
            $table->enum('status', ['PENDING', 'PROCESSING', 'SUCCESSFUL', 'FAILED', 'CANCELLED', 'REFUNDED', 'PARTIALLY_REFUNDED'])->default('PENDING');
            $table->string('provider')->default('MPESA');
            $table->string('transaction_reference')->collation(config('database.default') === 'sqlite' ? 'BINARY' : 'utf8mb4_bin')->nullable()->unique();
            $table->string('merchant_reference')->collation(config('database.default') === 'sqlite' ? 'BINARY' : 'utf8mb4_bin')->unique();
            $table->string('daraja_checkout_request_id')->collation(config('database.default') === 'sqlite' ? 'BINARY' : 'utf8mb4_bin')->nullable()->unique();
            $table->integer('amount');
            $table->string('phone_number', 30)->nullable();
            $table->json('provider_response')->nullable();
            $table->text('failure_reason')->nullable();
            $table->dateTime('paid_at', 3)->nullable();
            $table->dateTime('created_at', 3)->useCurrent();
            $table->index(['booking_id', 'status']);
            $table->foreign('booking_id')->references('id')->on('bookings');
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('payment_id', 36);
            $table->integer('amount');
            $table->text('reason');
            $table->dateTime('created_at', 3)->useCurrent();
            $table->foreign('payment_id')->references('id')->on('payments');
        });

        Schema::create('commissions', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('booking_id', 36)->unique();
            $table->double('rate');
            $table->integer('gross_amount');
            $table->integer('commission_amount');
            $table->integer('provider_amount');
            $table->string('payout_status')->default('PENDING');
            $table->foreign('booking_id')->references('id')->on('bookings');
        });

        Schema::create('payouts', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('provider_id', 36);
            $table->integer('amount');
            $table->string('status')->default('PENDING');
            $table->string('reference')->nullable();
            $table->dateTime('created_at', 3)->useCurrent();
        });

        Schema::create('conversations', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('booking_id', 36)->nullable();
            $table->string('title')->nullable();
            $table->dateTime('created_at', 3)->useCurrent();
            $table->dateTime('updated_at', 3);
            $table->foreign('booking_id')->references('id')->on('bookings');
        });

        Schema::create('conversation_participants', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('conversation_id', 36);
            $table->string('user_id', 36);
            $table->dateTime('last_read_at', 3)->nullable();
            $table->unique(['conversation_id', 'user_id']);
            $table->foreign('conversation_id')->references('id')->on('conversations')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('conversation_id', 36);
            $table->string('sender_id', 36);
            $table->text('body');
            $table->text('attachment_url')->nullable();
            $table->dateTime('created_at', 3)->useCurrent();
            $table->dateTime('read_at', 3)->nullable();
            $table->index(['conversation_id', 'created_at']);
            $table->foreign('conversation_id')->references('id')->on('conversations')->cascadeOnDelete();
            $table->foreign('sender_id')->references('id')->on('users');
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('user_id', 36);
            $table->string('title');
            $table->text('body');
            $table->string('type');
            $table->dateTime('read_at', 3)->nullable();
            $table->dateTime('created_at', 3)->useCurrent();
            $table->index(['user_id', 'read_at']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('booking_id', 36);
            $table->string('author_id', 36);
            $table->string('trip_id', 36)->nullable();
            $table->string('vehicle_id', 36)->nullable();
            $table->integer('rating');
            $table->integer('communication')->nullable();
            $table->integer('service')->nullable();
            $table->integer('quality')->nullable();
            $table->integer('value')->nullable();
            $table->text('body');
            $table->dateTime('created_at', 3)->useCurrent();
            $table->unique(['booking_id', 'author_id']);
            $table->foreign('booking_id')->references('id')->on('bookings');
            $table->foreign('author_id')->references('id')->on('users');
            $table->foreign('trip_id')->references('id')->on('trips');
            $table->foreign('vehicle_id')->references('id')->on('vehicles');
        });

        Schema::create('verification_requests', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('user_id', 36);
            $table->string('type');
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED', 'MORE_INFO'])->default('PENDING');
            $table->text('notes')->nullable();
            $table->json('documents')->nullable();
            $table->dateTime('created_at', 3)->useCurrent();
            $table->dateTime('reviewed_at', 3)->nullable();
            $table->foreign('user_id')->references('id')->on('users');
        });

        Schema::create('favorites', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('user_id', 36);
            $table->string('trip_id', 36)->nullable();
            $table->unique(['user_id', 'trip_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('trip_id')->references('id')->on('trips')->cascadeOnDelete();
        });

        Schema::create('events', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('title');
            $table->string('slug')->collation(config('database.default') === 'sqlite' ? 'BINARY' : 'utf8mb4_bin')->unique();
            $table->text('description');
            $table->text('image_url');
            $table->dateTime('starts_at', 3);
            $table->string('location');
            $table->boolean('published')->default(true);
        });

        Schema::create('blog_posts', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('title');
            $table->string('slug')->collation(config('database.default') === 'sqlite' ? 'BINARY' : 'utf8mb4_bin')->unique();
            $table->text('excerpt');
            $table->longText('body');
            $table->text('image_url');
            $table->boolean('published')->default(true);
            $table->dateTime('created_at', 3)->useCurrent();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('key')->collation(config('database.default') === 'sqlite' ? 'BINARY' : 'utf8mb4_bin')->unique();
            $table->text('value');
            $table->dateTime('updated_at', 3);
        });

        Schema::create('admin_logs', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('admin_id', 36);
            $table->string('action');
            $table->string('entity');
            $table->string('entity_id', 36)->nullable();
            $table->json('metadata')->nullable();
            $table->dateTime('created_at', 3)->useCurrent();
            $table->foreign('admin_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_logs');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('blog_posts');
        Schema::dropIfExists('events');
        Schema::dropIfExists('favorites');
        Schema::dropIfExists('verification_requests');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversation_participants');
        Schema::dropIfExists('conversations');
        Schema::dropIfExists('payouts');
        Schema::dropIfExists('commissions');
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('booking_passengers');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('trip_availabilities');
        Schema::dropIfExists('trip_images');
        Schema::dropIfExists('trips');
        Schema::dropIfExists('vehicle_availabilities');
        Schema::dropIfExists('vehicle_images');
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('trip_categories');
        Schema::dropIfExists('destinations');
        Schema::dropIfExists('otp_challenges');
        Schema::dropIfExists('vehicle_owner_profiles');
        Schema::dropIfExists('operator_profiles');
        Schema::dropIfExists('traveler_profiles');
    }
};
