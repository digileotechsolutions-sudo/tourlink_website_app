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
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('auth-google', function (Request $request): Limit {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('auth-otp-verify', function (Request $request): Limit {
            $userId = $request->string('user_id')->toString();
            $channel = $request->string('channel')->toString();

            return Limit::perMinute(8)->by($userId.'|'.$channel.'|'.$request->ip());
        });

        RateLimiter::for('auth-otp-resend', function (Request $request): array {
            $userId = $request->string('user_id')->toString();
            $channel = $request->string('channel')->toString();

            return [
                Limit::perMinute(1)->by($userId.'|'.$channel.'|'.$request->ip()),
                Limit::perHour(5)->by($userId.'|'.$channel),
                Limit::perHour(20)->by($request->ip()),
            ];
        });

        RateLimiter::for('password-reset', function (Request $request): Limit {
            return Limit::perMinute(3)->by(Str::lower($request->string('email')->toString()).'|'.$request->ip());
        });

        RateLimiter::for('support-chat', function (Request $request): Limit {
            return Limit::perMinute(30)->by(($request->user()?->getAuthIdentifier() ?? 'guest').'|'.$request->ip());
        });

        RateLimiter::for('payments', function (Request $request): Limit {
            return Limit::perMinute(5)->by($request->user()?->getAuthIdentifier() ?? $request->ip());
        });

        RateLimiter::for('locale-change', function (Request $request): Limit {
            return Limit::perMinute(20)->by(($request->user()?->getAuthIdentifier() ?? 'guest').'|'.$request->ip());
        });

        RateLimiter::for('mpesa-callback', function (Request $request): Limit {
            return Limit::perMinute(60)->by($request->ip());
        });
    }
}
