<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');
        $middleware->redirectTo(guests: '/login', users: '/dashboard');
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();

// Serverless hosts (e.g. Vercel) have a read-only filesystem except /tmp.
// Set APP_STORAGE_PATH=/tmp/storage there and storage is moved automatically.
$customStorage = getenv('APP_STORAGE_PATH') ?: ($_SERVER['APP_STORAGE_PATH'] ?? null);
if ($customStorage) {
    foreach (['framework/views', 'framework/cache/data', 'framework/sessions', 'logs'] as $dir) {
        if (! is_dir("$customStorage/$dir")) {
            @mkdir("$customStorage/$dir", 0755, true);
        }
    }
    $app->useStoragePath($customStorage);
}

return $app;
