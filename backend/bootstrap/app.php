<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        App\Console\Commands\ImportSantriRekapCommand::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => App\Http\Middleware\RoleMiddleware::class,
        ]);

        $middleware->redirectUsersTo(function ($request) {
            return $request->user()?->role === 'admin' ? '/dbAdmin' : '/dbSantri';
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
