<?php

namespace App\Http\Middleware;

use App\AccountApprovalStatus;
use App\AccountStatus;
use App\Services\Verification\AccountVerificationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountAccess
{
    public function __construct(private readonly AccountVerificationService $verification) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => 'Authentication is required.'], Response::HTTP_UNAUTHORIZED);
            }

            return redirect()->guest(route('login'));
        }

        if (! $this->verification->isFullyVerified($user)) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => 'Account verification is required.',
                    'pending_channels' => array_map(
                        fn ($channel): string => $channel->value,
                        $this->verification->pendingChannels($user),
                    ),
                ], Response::HTTP_FORBIDDEN);
            }

            return redirect()->route('verification.notice', ['user' => $user->id]);
        }

        if ($user->account_status !== AccountStatus::Active
            || $user->approval_status !== AccountApprovalStatus::Approved) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => 'Your account is not currently authorized.'], Response::HTTP_FORBIDDEN);
            }

            return redirect()->route('login')->withErrors(['email' => 'Your account is not currently authorized to use TourLink.']);
        }

        if ($roles !== [] && ! in_array($user->role->value, $roles, true)) {
            abort(Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
