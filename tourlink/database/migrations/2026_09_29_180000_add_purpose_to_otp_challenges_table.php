<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('otp_challenges', function (Blueprint $table): void {
            $table->string('purpose', 80)->default('account_verification')->after('channel');
            $table->index(['user_id', 'purpose', 'channel', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('otp_challenges', function (Blueprint $table): void {
            $table->dropIndex(['otp_challenges_user_id_purpose_channel_created_at_index']);
            $table->dropColumn('purpose');
        });
    }
};
