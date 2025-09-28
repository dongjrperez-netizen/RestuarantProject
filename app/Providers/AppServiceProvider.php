<?php

namespace App\Providers;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //       Event::listen(Login::class, function ($event) {
        //     // Reset verification status each login
        //     $event->user->forceFill([
        //         'email_verified_at' => null
        //     ])->save();

        //     // Send a new verification email
        //     $event->user->sendEmailVerificationNotification();
        // });
    }
}
