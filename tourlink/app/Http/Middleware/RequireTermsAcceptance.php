<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireTermsAcceptance
{
    public const COOKIE_NAME = 'havenedge_terms_acceptance';

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->isRegistrationRequest($request)) {
            return $next($request);
        }

        $expectedAcceptance = self::tokenFor();
        $storedAcceptance = (string) $request->cookie(self::COOKIE_NAME, '');

        if (hash_equals($expectedAcceptance, $storedAcceptance)) {
            return $next($request);
        }

        if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
            $destination = '/'.$request->path();
            if ($request->getQueryString()) {
                $destination .= '?'.$request->getQueryString();
            }
            $request->session()->put('terms.intended', $destination);
        } else {
            $request->session()->put('terms.intended', $this->registrationDestination($request));
        }

        return redirect()->route('terms');
    }

    private function isRegistrationRequest(Request $request): bool
    {
        if ($request->is('register')) {
            return true;
        }

        if ($request->routeIs('google.authenticate')) {
            return $request->input('mode') === 'register';
        }

        return $request->routeIs('google.complete')
            && $request->session()->get('google.pending_profile.mode') === 'register';
    }

    private function registrationDestination(Request $request): string
    {
        if ($request->routeIs('google.complete')) {
            return route('google.complete');
        }

        if ($request->is('register')) {
            $role = $request->input('role');

            return route('register', $role ? ['role' => $role] : []);
        }

        $role = $request->input('role') ?? $request->session()->get('google.pending_profile.role');

        return route('register', $role ? ['role' => $role] : []);
    }

    public static function tokenFor(): string
    {
        return hash_hmac(
            'sha256',
            (string) config('app.terms_version'),
            (string) config('app.key'),
        );
    }
}