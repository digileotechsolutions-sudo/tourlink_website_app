<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\Auth\AccountSessionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class EnforceSingleAccountSession
{
    public function __construct(private readonly AccountSessionService $sessions) {}

    public function handle(Request $request, Closure $next): Response
    {
        $isApiRequest = $request->is('api/*');
        $user = $isApiRequest ? Auth::guard('sanctum')->user() : $request->user();

        if (! $user instanceof User) {
            return $next($request);
        }

        $token = $user->currentAccessToken();
        $valid = $token instanceof PersonalAccessToken
            ? $this->sessions->validateToken($user, $token, $request)
            : ($request->hasSession() && $this->sessions->validateWebSession($user, $request));

        if (! $valid && ! ($token instanceof PersonalAccessToken) && $request->hasSession()
            && $request->session()->get('_account_session_fingerprint') === null) {
            $valid = $this->sessions->registerWebLogin($user, $request, $request->session()->getId());
        }

        if ($valid) {
            return $next($request);
        }

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        } else {
            if ($request->hasSession() && $request->session()->get('_account_session_fingerprint') !== null) {
                $user->forceFill(['remember_token' => Str::random(60)])->save();
            }
            Auth::guard('web')->logout();
            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }
        }

        if (! ($token instanceof PersonalAccessToken) && $request->is('login')) {
            return $next($request);
        }

        if ($isApiRequest || $request->expectsJson()) {
            return response()->json([
                'message' => 'This authenticated session is no longer active. Please sign in again.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return redirect()->route('login')
            ->withErrors(['email' => 'Your session is no longer active. Please sign in again.']);
    }
}
