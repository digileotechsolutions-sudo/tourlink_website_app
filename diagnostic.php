<?php

/**
 * Standalone diagnosis page for a 403 response.
 *
 * cPanel hosts regularly answer with a bare "403 Forbidden" when the archive was
 * extracted into the wrong folder or a file lost its permissions, which gives no
 * clue about the cause. This script reports the layout it can actually see and
 * the exact steps needed to fix it. It deliberately avoids the framework so it
 * still runs when the application cannot boot, and it only prints folder names,
 * never secret values or the absolute home path.
 *
 * It is wired to Apache's 403 handler through public_html/.htaccess and can also
 * be opened directly. Delete the ErrorDocument line once the site loads.
 */
$webRoot = __DIR__;
$home = dirname($webRoot);

$listNames = static function (string $directory): array {
    if (! is_dir($directory) || ! is_readable($directory)) {
        return [];
    }

    return array_values(array_diff(@scandir($directory) ?: [], ['.', '..', '.htaccess']));
};

$describe = static function (string $label, array $names): string {
    if ($names === []) {
        return $label.' is empty or not readable by the web server.';
    }

    $shown = array_slice($names, 0, 25);
    $more = count($names) > count($shown) ? ' (+'.(count($names) - count($shown)).' more)' : '';

    return $label.' contains: '.implode(', ', $shown).$more;
};

$isApplication = static fn (string $directory): bool => is_file($directory.'/artisan') && is_file($directory.'/vendor/autoload.php');

// The supported layout is /home/<user>/public_html for the document root and
// /home/<user>/tourlink for the application, which is what the front controller
// resolves through its "../tourlink" candidate. Because the document root is
// always named public_html, a parent folder with that name means this script is
// being served from one folder too deep.
$canonicalApplication = $home.'/tourlink';
$servedInsideDocumentRoot = basename($home) === 'public_html' && is_dir($home.'/public_html');

$layout = 'unrecognised';
$application = null;

if ($servedInsideDocumentRoot) {
    $layout = 'nested';
} elseif ($isApplication($canonicalApplication)) {
    $layout = 'correct';
    $application = $canonicalApplication;
} elseif ($isApplication($webRoot)) {
    $layout = 'application in the document root';
    $application = $webRoot;
} elseif ($isApplication($home)) {
    $layout = 'application in the home folder';
    $application = $home;
} elseif (is_dir($webRoot.'/tourlink')) {
    $layout = 'application inside the document root';
    $application = $isApplication($webRoot.'/tourlink') ? $webRoot.'/tourlink' : null;
}

$checks = [
    'PHP '.PHP_VERSION.(PHP_VERSION_ID >= 80401 ? ' - ok (8.4.1 or newer)' : ' - too old, 8.4.1 or newer required'),
    'index.php: '.(is_file($webRoot.'/index.php')
        ? (is_readable($webRoot.'/index.php') ? 'present and readable - ok' : 'present but NOT readable by the web server')
        : 'missing from the folder being served'),
];

if ($application !== null) {
    $checks[] = 'Laravel application: found in "'.basename($application).'"';
    $checks[] = '.env: '.(is_file($application.'/.env') ? 'present - ok' : 'missing, see the steps below');

    foreach (['storage/logs', 'storage/framework/views', 'storage/framework/cache', 'bootstrap/cache'] as $relative) {
        $path = $application.'/'.$relative;
        $checks[] = $relative.': '.(is_dir($path)
            ? (is_writable($path) ? 'writable - ok' : 'exists but NOT writable, set it to 755 or 775')
            : 'created automatically on the first request');
    }
} else {
    $checks[] = 'Laravel application: not found beside the folder being served';
}

$steps = match ($layout) {
    'nested' => [
        'The archive was extracted one folder too deep.',
        'In cPanel File Manager, open /home/havenedg/public_html. You should find a "public_html" folder and a "tourlink" folder inside it.',
        'Select the "tourlink" folder, click Move, and move it up into /home/havenedg.',
        'The end state must be /home/havenedg/public_html and /home/havenedg/tourlink sitting side by side, with nothing wrapping them.',
        'Reload the site afterwards.',
    ],
    'application inside the document root' => [
        'The application is inside the document root. The root index.php supports this layout, while .htaccess blocks direct access to the protected /tourlink folder.',
        'Open the website routes from the domain root, for example /login or /register. Legacy /tourlink/login and /tourlink/register URLs redirect to the matching public route.',
        'If the domain-root /login still returns 403, check cPanel Errors for the exact request and hosting rule.',
    ],
    'unrecognised' => [
        'Extract the archive to /home/havenedg so that public_html and tourlink become two sibling folders there.',
        'Do not extract it inside public_html itself.',
        'If the archive did not produce a "tourlink" folder, the extraction was incomplete, so upload and extract it again.',
    ],
    default => [],
};

if (is_file($webRoot.'/index.php') && ! is_readable($webRoot.'/index.php')) {
    $steps[] = 'Set index.php and .htaccess in the served folder to 644, and the folder itself to 755.';
}

if ($application !== null && ! is_file($application.'/.env')) {
    $steps[] = 'Upload tourlink-production.env into /home/havenedg/tourlink and rename it to .env, then set MAIL_PASSWORD.';
}

if ($steps === []) {
    $steps[] = 'The layout looks correct, so the 403 is coming from the server configuration rather than the files.';
    $steps[] = 'Open cPanel -> Errors for this domain, which records the exact reason, and confirm the document root is /home/havenedg/public_html.';
    $steps[] = 'If a PHP version other than 8.4.1+ is selected for this domain, switch it in cPanel -> MultiPHP Manager.';
}

$sections = [
    'Layout' => [
        'Verdict: '.$layout,
        $describe('the folder being served', $listNames($webRoot)),
        $describe('the folder above it', $listNames($home)),
    ],
    'What the server sees' => $checks,
    'What to do' => $steps,
    'Reference' => [
        'Supported layouts: /home/havenedg/public_html beside /home/havenedg/tourlink, or public_html/tourlink protected by its root .htaccess and front controller.',
        'This page reads folder names only. It never displays environment values or the absolute home path.',
    ],
];

$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES);

http_response_code(200);
header('Content-Type: text/html; charset=utf-8');

echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">'
    .'<meta name="viewport" content="width=device-width, initial-scale=1">'
    .'<title>TourLink 403 diagnosis</title>'
    .'<style>body{font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;background:#0f172a;color:#e2e8f0;'
    .'margin:0;padding:2.5rem}main{max-width:52rem;margin:0 auto}h1{font-size:1.4rem;margin:0 0 1.5rem}'
    .'h2{font-size:1.05rem;margin:2rem 0 .5rem;color:#7dd3fc}p,li{line-height:1.65;color:#cbd5e1}'
    .'li{margin:.2rem 0}ul{padding-left:1.25rem;margin:.5rem 0}</style></head><body><main>'
    .'<h1>TourLink 403 diagnosis</h1>';

foreach ($sections as $heading => $items) {
    echo '<h2>'.$escape($heading).'</h2><ul>'.implode('', array_map(
        static fn (string $item): string => '<li>'.$escape($item).'</li>',
        $items,
    )).'</ul>';
}

echo '</main></body></html>';
