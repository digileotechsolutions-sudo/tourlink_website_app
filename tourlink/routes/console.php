<?php

use App\Models\OtpChallenge;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('model:prune', ['--model' => OtpChallenge::class])->hourly()->withoutOverlapping();
Schedule::command('auth:clear-resets')->everyFifteenMinutes()->withoutOverlapping();
