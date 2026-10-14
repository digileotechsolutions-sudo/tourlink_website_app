<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->ulid('tokenable_id')->change();
        });
    }

    public function down(): void
    {
        if (DB::table('personal_access_tokens')->exists()) {
            throw new \RuntimeException('Cannot roll back tokenable IDs to integers while personal access tokens exist.');
        }

        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->unsignedBigInteger('tokenable_id')->change();
        });
    }
};
