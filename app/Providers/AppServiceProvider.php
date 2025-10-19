<?php

declare(strict_types=1);

namespace App\Providers;

use App\Interfaces\DataSourceInterface;
use App\Models\MailboxerConversation;
use App\Models\MailboxerConversationOptOut;
use App\Models\MailboxerNotification;
use App\Models\MailboxerReceipt;
use App\Models\User;
use App\Services\FitbitService;
use App\Services\GarminService;
use App\Services\OuraRingService;
use App\Services\StravaService;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
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
                    'ouraring' => app(OuraRingService::class),
                ]);
            }
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrap();
        $this->configureUrls();

        Relation::morphMap([
            // User model (used in sender_type, receiver_type, unsubscriber_type)
            'User' => User::class,

            // Mailboxer-related models
            'MailboxerNotification' => MailboxerNotification::class,
            'MailboxerReceipt' => MailboxerReceipt::class,
            'MailboxerConversation' => MailboxerConversation::class,
            'MailboxerConversationOptOut' => MailboxerConversationOptOut::class,
        ]);
    }

    /**
     * Configure the application's URLs.
     */
    private function configureUrls(): void
    {
        URL::forceScheme('https');
    }
}
