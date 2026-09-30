<?php

namespace App\Http\Middleware;

use App\AccountApprovalStatus;
use App\AccountStatus;
use App\Role;
use App\Services\Verification\AccountVerificationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function __construct(private readonly AccountVerificationService $verification) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return redirect()->guest(route('login'));
        }

        abort_unless($request->user()->role === Role::Admin, Response::HTTP_FORBIDDEN);

        if (! $this->verification->isFullyVerified($request->user())) {
            return redirect()->route('verification.notice', ['user' => $request->user()->id]);
        }

        if ($request->user()->account_status !== AccountStatus::Active
            || $request->user()->approval_status !== AccountApprovalStatus::Approved) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => 'Your administrator account is not currently authorized.']);
        }

        return $next($request);
    }
}
