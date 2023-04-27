<?php

namespace App\Providers;

use App\Contracts\Services\AchievementServiceInterface;
use App\Services\AchievementService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AchievementServiceInterface::class, function() {
            return resolve(AchievementService::class);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
