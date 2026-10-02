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
        if ($request->routeIs('terms', 'terms.accept')) {
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
        }

        return redirect()->route('terms');
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