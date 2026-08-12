<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\InfobipService;
use Illuminate\Support\Facades\Blade;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(InfobipService::class, function ($app) {
            return new InfobipService();
        });
    }

    public function boot()
    {
        Blade::component('layouts.auth', 'layouts.auth');

        \App\Models\Inventory::observe(\App\Observers\InventoryObserver::class);
        \App\Models\InventoryOutput::observe(\App\Observers\InventoryOutputObserver::class);
    }
}
