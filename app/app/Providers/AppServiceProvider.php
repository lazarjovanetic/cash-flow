<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
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
        // Starije MySQL/MariaDB verzije na shared hostingu ne dozvoljavaju indekse duže od 767 bajtova.
        Schema::defaultStringLength(191);
    }
}
