<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `personal_access_tokens` MODIFY `tokenable_id` CHAR(26) NOT NULL');

            return;
        }

        Schema::table('personal_access_tokens', function (Blueprint $table): void {
            $table->char('tokenable_id', 26)->change();
        });
    }

    public function down(): void
    {
        if (DB::table('personal_access_tokens')->exists()) {
            throw new \RuntimeException('Cannot roll back tokenable IDs to integers while personal access tokens exist.');
        }

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `personal_access_tokens` MODIFY `tokenable_id` BIGINT UNSIGNED NOT NULL');

            return;
        }

        Schema::table('personal_access_tokens', function (Blueprint $table): void {
            $table->unsignedBigInteger('tokenable_id')->change();
        });
    }
};
