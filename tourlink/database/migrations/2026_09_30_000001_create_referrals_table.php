<?php

use App\ReferralRewardStatus;
use App\ReferralStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('referral_code', 16)->nullable()->after('password')
                ->collation(config('database.default') === 'sqlite' ? 'BINARY' : 'utf8mb4_bin')
                ->unique();
        });

        Schema::create('referrals', function (Blueprint $table): void {
            $table->string('id', 36)->primary();
            $table->string('referrer_id', 36);
            $table->string('referred_user_id', 36);
            $table->string('referral_code', 16);
            $table->enum('status', array_map(fn (ReferralStatus $status): string => $status->value, ReferralStatus::cases()))->default(ReferralStatus::Pending->value);
            $table->enum('reward_status', array_map(fn (ReferralRewardStatus $status): string => $status->value, ReferralRewardStatus::cases()))->default(ReferralRewardStatus::None->value);
            $table->unsignedInteger('reward_amount')->default(0);
            $table->dateTime('verified_at', 3)->nullable();
            $table->dateTime('approved_at', 3)->nullable();
            $table->dateTime('rewarded_at', 3)->nullable();
            $table->dateTime('created_at', 3)->useCurrent();
            $table->dateTime('updated_at', 3)->useCurrent();

            // A referred account can only ever belong to one referrer. This is
            // the database-level guarantee behind the anti-abuse rule.
            $table->unique('referred_user_id');
            $table->index(['referrer_id', 'status']);
            $table->index(['referrer_id', 'created_at']);
            $table->index('status');
            $table->foreign('referrer_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('referred_user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        $defaults = [
            'referral.reward_enabled' => '0',
            'referral.reward_amount' => '100',
            'referral.reward_currency' => 'KES',
            'referral.reward_trigger' => ReferralStatus::Approved->value,
            'referral.reward_requires_approval' => '1',
            'referral.code_prefix' => 'TL',
            'referral.code_length' => '6',
        ];

        foreach ($defaults as $key => $value) {
            DB::table('settings')->insertOrIgnore([
                'id' => (string) Str::ulid(),
                'key' => $key,
                'value' => $value,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', [
            'referral.reward_enabled',
            'referral.reward_amount',
            'referral.reward_currency',
            'referral.reward_trigger',
            'referral.reward_requires_approval',
            'referral.code_prefix',
            'referral.code_length',
        ])->delete();

        Schema::dropIfExists('referrals');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['referral_code']);
            $table->dropColumn('referral_code');
        });
    }
};
