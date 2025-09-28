<?php

use App\Http\Middleware\AdminAuth;
use App\Http\Middleware\CheckDemoSubscription;
use App\Http\Middleware\CheckSubscription;
use App\Http\Middleware\EmployeeAuth;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::prefix('supplier')
                ->middleware('web')
                ->group(base_path('routes/supplier.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'check.demo.subscription' => CheckDemoSubscription::class,
            'check.subscription' => CheckSubscription::class,
            'admin.auth' => AdminAuth::class,
            'employee.auth' => EmployeeAuth::class,
            'role' => RoleMiddleware::class,
        ]);
    })

    ->withSchedule(function ($schedule) {
        // Archive expired menu plans daily at midnight
        $schedule->command('menu-plans:archive-expired')
            ->daily()
            ->at('00:01')
            ->name('archive-expired-menu-plans')
            ->description('Archive menu plans that have passed their end date');

        // Update expired table reservations every 15 minutes
        $schedule->command('reservations:update-expired')
            ->everyFifteenMinutes()
            ->name('update-expired-reservations')
            ->description('Update table status to available for expired reservations');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
