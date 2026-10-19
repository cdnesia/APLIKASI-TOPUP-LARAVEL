<?php

namespace App\Providers;

use App\Services\Digiflazz\DigiflazzClient;
use App\Services\Duitku\DuitkuClient;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(DigiflazzClient::class, fn () => DigiflazzClient::fromConfig());
        $this->app->singleton(DuitkuClient::class, fn () => DuitkuClient::fromConfig());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('pagination::tailwind');
        Paginator::defaultSimpleView('pagination::simple-tailwind');
    }
}
