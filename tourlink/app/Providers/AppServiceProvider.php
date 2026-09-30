<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('auth-login', function (Request $request): Limit {
            return Limit::perMinute(5)->by(Str::lower($request->string('email')->toString()).'|'.$request->ip());
        });

        RateLimiter::for('auth-register', function (Request $request): Limit {
            return Limit::perMinute(3)->by($request->ip());
        });

        RateLimiter::for('auth-otp', function (Request $request): Limit {
            $userId = $request->string('user_id')->toString();
            $channel = $request->string('channel')->toString();

            return Limit::perMinute(8)->by($userId.'|'.$channel.'|'.$request->ip());
        });

        RateLimiter::for('password-reset', function (Request $request): Limit {
            return Limit::perMinute(3)->by(Str::lower($request->string('email')->toString()).'|'.$request->ip());
        });

        RateLimiter::for('payments', function (Request $request): Limit {
            return Limit::perMinute(5)->by($request->user()?->getAuthIdentifier() ?? $request->ip());
        });

        RateLimiter::for('mpesa-callback', function (Request $request): Limit {
            return Limit::perMinute(60)->by($request->ip());
        });
    }
}
