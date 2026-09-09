<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
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
        // The whole app is Bootstrap-styled with no Tailwind anywhere —
        // Laravel's default pagination view is Tailwind-based, which would
        // render unstyled here. Bootstrap is the one built-in alternative
        // that actually matches.
        Paginator::useBootstrap();
    }
}
