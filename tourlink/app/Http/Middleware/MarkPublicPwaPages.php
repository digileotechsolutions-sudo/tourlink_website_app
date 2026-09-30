<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MarkPublicPwaPages
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->isMethod('GET') && $request->query->count() === 0 && $request->user() === null && $response->isSuccessful()) {
            $response->headers->set('X-PWA-Cacheable', 'public');
            $response->headers->set('Cache-Control', 'public, max-age=0, must-revalidate');
        }

        return $response;
    }
}