<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('account_sessions');
    }

    public function down(): void
    {
        // Account session history is intentionally discarded and is not restored.
    }
};
