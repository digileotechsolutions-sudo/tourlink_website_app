<?php

use App\Http\Middleware\EnsureAccountAccess;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\MarkPublicPwaPages;
use App\Http\Middleware\RequireTermsAcceptance;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withBroadcasting(__DIR__.'/../routes/channels.php')
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('web', RequireTermsAcceptance::class);
        $middleware->alias([
            'account.access' => EnsureAccountAccess::class,
            'admin' => EnsureAdmin::class,
            'pwa.public' => MarkPublicPwaPages::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
