<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\Auth\AccountSessionService;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureCurrentAccountSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return $next($request);
        }

        $currentVersion = (int) $user->session_version;
        $sessionVersion = $request->session()->get(AccountSessionService::SESSION_VERSION_KEY);
        $sessionStartedAt = $request->session()->get(AccountSessionService::SESSION_STARTED_AT_KEY);

        if ($sessionVersion === null && $currentVersion === 1) {
            $sessionStartedAt = now()->timestamp;
            $request->session()->put([
                AccountSessionService::SESSION_VERSION_KEY => $currentVersion,
                AccountSessionService::SESSION_STARTED_AT_KEY => $sessionStartedAt,
            ]);
        }

        $maximumLifetime = max(1, (int) config('session.absolute_lifetime', 720)) * 60;
        $now = now()->timestamp;
        $isWithinAbsoluteLifetime = is_numeric($sessionStartedAt)
            && (int) $sessionStartedAt <= $now
            && $now - (int) $sessionStartedAt < $maximumLifetime;

        if (is_numeric($sessionVersion)
            && (int) $sessionVersion === $currentVersion
            && $isWithinAbsoluteLifetime) {
            return $next($request);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        throw new AuthenticationException('Unauthenticated.', ['web'], route('login'));
    }
}
