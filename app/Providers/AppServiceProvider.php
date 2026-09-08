<?php

namespace App\Providers;

use App\Contracts\GoogleCalendarGateway;
use App\Models\Order;
use App\Observers\OrderObserver;
use App\Services\GoogleCalendarService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(GoogleCalendarGateway::class, GoogleCalendarService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Order::observe(OrderObserver::class);
    }
}
