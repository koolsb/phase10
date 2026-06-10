<?php

namespace App\Providers;

use App\Services\PhaseLibrary;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PhaseLibrary::class, fn (): PhaseLibrary => new PhaseLibrary(
            config: config('phases', []),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
