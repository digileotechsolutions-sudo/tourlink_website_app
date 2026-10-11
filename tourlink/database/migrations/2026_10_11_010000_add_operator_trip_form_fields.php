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
            $table->string('meeting_point')->nullable()->after('ending_point');
            $table->string('transport_type', 40)->nullable()->after('transport');
            $table->unsignedInteger('child_price')->nullable()->after('price_per_person');
            $table->unsignedInteger('deposit_amount')->nullable()->after('child_price');
            $table->json('additional_charges')->nullable()->after('excluded_items');
            $table->json('highlights')->nullable()->after('activities');
            $table->dateTime('submitted_at', 3)->nullable()->after('updated_at');
        });

        DB::table('trips')
            ->where('verification_status', 'PENDING')
            ->update(['submitted_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table): void {
            $table->dropColumn([
                'meeting_point',
                'transport_type',
                'child_price',
                'deposit_amount',
                'additional_charges',
                'highlights',
                'submitted_at',
            ]);
        });
    }
};
