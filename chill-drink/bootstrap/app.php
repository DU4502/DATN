<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectGuestsTo(function (\Illuminate\Http\Request $request) {
            if (! $request->expectsJson()) {
                if ($request->is('chat*') || $request->is('admin/chat*')) {
                    session()->forget('url.intended');
                }

                return url('/login');
            }
        });

        $middleware->web(append: [
            \App\Http\Middleware\AutoCompleteDeliveredOrders::class,
        ]);

        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'superadmin' => \App\Http\Middleware\SuperAdminMiddleware::class,
            'cskh' => \App\Http\Middleware\CskhMiddleware::class,
            'staff' => \App\Http\Middleware\StaffMiddleware::class,
            'shipper' => \App\Http\Middleware\ShipperMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
