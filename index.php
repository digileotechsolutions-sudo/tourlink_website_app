<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

/**
 * Front controller for hosts where the document root cannot point at the
 * application directory, for example:
 *
 *   /home/havenedg/public_html   <- document root
 *   /home/havenedg/tourlink      <- Laravel application
 *
 * It locates the application, makes sure the writable directories exist, and
 * reports exactly what is wrong instead of returning an unexplained 500.
 */
$fail = function (string $reason, string $detail): never {
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');

    echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">'
        .'<meta name="viewport" content="width=device-width, initial-scale=1">'
        .'<title>TourLink configuration problem</title>'
        .'<style>body{font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;'
        .'background:#0f172a;color:#e2e8f0;margin:0;padding:2.5rem}'
        .'main{max-width:44rem;margin:0 auto}'
        .'h1{font-size:1.35rem;margin:0 0 .5rem}'
        .'p{line-height:1.6;color:#cbd5e1}'
        .'code{background:#1e293b;padding:.15rem .4rem;border-radius:.25rem}'
        .'ul{line-height:1.8;color:#cbd5e1}</style></head><body><main>'
        .'<h1>'.htmlspecialchars($reason, ENT_QUOTES).'</h1>'
        .'<p>'.htmlspecialchars($detail, ENT_QUOTES).'</p>'
        .'<p>Check the cPanel error log for this domain, or open '
        .'<code>tourlink/storage/logs/laravel.log</code> for the full stack trace.</p>'
        .'</main></body></html>';

    exit;
};

$candidates = [
    __DIR__.'/tourlink',
    __DIR__.'/..',
    __DIR__.'/app',
    __DIR__.'/laravel',
    __DIR__.'/application',
];

$app = null;

foreach ($candidates as $candidate) {
    if (is_file($candidate.'/vendor/autoload.php') && is_file($candidate.'/artisan')) {
        $app = realpath($candidate) ?: $candidate;
        break;
    }
}

if ($app === null) {
    $fail(
        'TourLink could not locate the application files.',
        'Looked for vendor/autoload.php next to this folder and in the parent folders. '
        .'Extract the archive so that the Laravel application sits in a tourlink folder beside public_html.',
    );
}

foreach (['/storage/framework/views', '/storage/framework/cache', '/storage/logs', '/bootstrap/cache'] as $writable) {
    $path = $app.$writable;

    if (! is_dir($path)) {
        @mkdir($path, 0775, true);
    }

    if (! is_dir($path) || ! is_writable($path)) {
        $fail(
            'TourLink cannot write to '.$writable.'.',
            'Set this folder and its contents to 755 or 775, owned by the account user, then reload.',
        );
    }
}

$cachedConfig = is_file($app.'/bootstrap/cache/config.php');
$environmentFile = $app.'/.env';

if (! $cachedConfig && ! is_file($environmentFile)) {
    $fail(
        'The TourLink environment file is missing.',
        'Copy .env.example to .env in the application folder, fill in the database and mail values, '
        .'then run php artisan key:generate.',
    );
}

if (! $cachedConfig && is_file($environmentFile)) {
    $key = null;
    $lines = preg_split('/\R/', (string) file_get_contents($environmentFile)) ?: [];

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#') || ! str_starts_with($line, 'APP_KEY')) {
            continue;
        }

        $key = trim(explode('=', $line, 2)[1] ?? '', " \t\"'");
        break;
    }

    if ($key === null || $key === '') {
        $fail(
            'TourLink has no application encryption key.',
            'Run /usr/local/bin/php '.basename($app).'/artisan key:generate, then reload.',
        );
    }
}

define('LARAVEL_START', microtime(true));

if (file_exists($maintenance = $app.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $app.'/vendor/autoload.php';

/** @var Application $application */
$application = require_once $app.'/bootstrap/app.php';

$application->handleRequest(Request::capture());
