<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class ApplyLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $languages = config('localization.languages', []);
        $locale = $request->session()->get('locale')
            ?? $request->cookie(config('localization.cookie'));

        if (! is_string($locale) || ! array_key_exists($locale, $languages)) {
            $locale = (string) config('app.locale', 'en');
        }

        if (! array_key_exists($locale, $languages)) {
            $locale = 'en';
        }

        App::setLocale($locale);

        return $next($request);
    }
}
