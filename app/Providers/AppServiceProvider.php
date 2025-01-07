<?php

namespace App\Providers;

use Essa\APIToolKit\Exceptions\Handler;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->environment('local') && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
        //
        $this->app->bind(ExceptionHandler::class, Handler::class); // for api toolkit
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
