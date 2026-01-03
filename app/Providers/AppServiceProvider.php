<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(\App\Services\GmailService::class, function ($app) {
            $user = auth()->user();
            if (!$user) {
                throw new \Exception('GmailService requires an authenticated user.');
            }
            return new \App\Services\GmailService($user);
        });
    }

    public function boot(): void
    {
        Paginator::useTailwind();
    }
}
