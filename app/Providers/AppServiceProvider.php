<?php

namespace App\Providers;

use App\Contracts\Services\AchievementServiceInterface;
use App\Contracts\Services\PayoutServiceInterface;
use App\Services\AchievementService;
use App\Services\PayStackPayoutService;
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

        $this->app->bind(PayoutServiceInterface::class, function() {
            return resolve(PayStackPayoutService::class);
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
