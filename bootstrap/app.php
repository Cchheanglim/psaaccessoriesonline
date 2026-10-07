<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Global web and API middleware configuration
        // Hosts like Render serve HTTPS through a proxy; trust it so links and cookies use https.
        $middleware->trustProxies(at: '*');

        // The storefront's login page is public/login.html, not the old Blade /login route.
        $middleware->redirectGuestsTo(fn () => '/login.html');
        // Old chats are cleaned up once an hour (see OrderMessage::cleanUp).
        $middleware->web(append: [\App\Http\Middleware\CleanUpOldMessages::class]);

        $middleware->alias([
            'staff' => \App\Http\Middleware\EnsureStaff::class,
            'same-account' => \App\Http\Middleware\EnsureSameAccount::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Exception handler configuration
    })->create();
