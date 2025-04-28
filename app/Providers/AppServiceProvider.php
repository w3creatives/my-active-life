<?php

declare(strict_types=1);

namespace App\Providers;

use App\Interfaces\DataSource;
use App\Services\FitbitService;
use App\Services\StravaService;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        app()->bind(
            DataSource::class,
            function ($app) {
                return collect([
                    'fitbit' => app(FitbitService::class),
                    'strava' => app(StravaService::class),
                    'garmin' => app(StravaService::class),
                ]);
            }
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
