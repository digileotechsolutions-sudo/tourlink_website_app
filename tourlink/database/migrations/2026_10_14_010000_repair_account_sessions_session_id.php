<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('account_sessions')) {
            throw new RuntimeException('Cannot repair account_sessions.session_id because account_sessions does not exist.');
        }

        if (Schema::hasColumn('account_sessions', 'session_id')) {
            return;
        }

        Schema::table('account_sessions', function (Blueprint $table): void {
            $table->string('session_id')->nullable()->after('session_type');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('account_sessions', 'session_id')) {
            Schema::table('account_sessions', function (Blueprint $table): void {
                $table->dropColumn('session_id');
            });
        }
    }
};
