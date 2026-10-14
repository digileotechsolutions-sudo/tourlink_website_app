<?php

use App\Http\Middleware\EnsureAccountAccess;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\ApplyLocale;
use App\Http\Middleware\MarkPublicPwaPages;
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
        $middleware->alias([
            'account.access' => EnsureAccountAccess::class,
            'admin' => EnsureAdmin::class,
            'pwa.public' => MarkPublicPwaPages::class,
        ]);
        $middleware->appendToGroup('web', ApplyLocale::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash([
            'mpesa_consumer_key',
            'mpesa_consumer_secret',
            'mpesa_passkey',
            'pesapal_consumer_key',
            'pesapal_consumer_secret',
        ]);
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
