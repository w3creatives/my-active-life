<?php

declare(strict_types=1);

namespace App\Providers;

use App\Interfaces\DataSourceInterface;
use App\Services\FitbitService;
use App\Services\GarminService;
use App\Services\StravaService;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->configureUrls();

        app()->bind(
            DataSourceInterface::class,
            function ($app) {
                return collect([
                    'fitbit' => app(FitbitService::class),
                    'strava' => app(StravaService::class),
                    'garmin' => app(GarminService::class),
                ]);
            }
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
    }

    /**
     * Configure the application's URLs.
     */
    private function configureUrls(): void
    {
        URL::forceScheme('https');
    }
}
