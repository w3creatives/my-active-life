<?php

declare(strict_types=1);

namespace App\Services;

use App\Interfaces\DataSourceInterface;
use App\Models\User;
use App\Traits\CalculateDaysTrait;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

final class FitbitService implements DataSourceInterface
{
    use CalculateDaysTrait;

    private string $apiUrl;

    private string $accessToken;

    private string $clientId;

    private string $redirectUrl;

    private string $clientSecret;

    private string $authUrl = 'https://www.fitbit.com/oauth2/authorize';

    private string $authTokenUrl = 'https://api.fitbit.com/oauth2/token';

    private string $activityBaseUrl = 'https://api.fitbit.com/1/';

    private string $fitbitWebhookVerificationCode;

    private array $authResponse;

    private CarbonImmutable $startDate;

    private CarbonImmutable $endDate;

    private float $dateDays;

    public function __construct($accessToken = '')
    {
        $this->accessToken = $accessToken;

        $this->clientId = config('services.fitbit.client_id');
        $this->redirectUrl = config('services.fitbit.redirect_url');
        $this->clientSecret = config('services.fitbit.client_secret');
        $this->fitbitWebhookVerificationCode = config('services.fitbit.webhook_verification_code');
    }

    public function setSecrets($secrets): self
    {
        if (is_array($secrets)) {
            [$accessToken] = $secrets;
        } else {
            $accessToken = $secrets;
        }

        $this->setAccessToken($accessToken);

        return $this;
    }

    public function setAccessToken($accessToken): self
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    public function setAccessTokenSecret($accessTokenSecret): self
    {
        return $this;
    }

    public function authUrl(): string
    {
        return $this->authUrl.'?'.http_build_query([
            'client_id' => $this->clientId,
            'response_type' => 'code',
            'redirect_uri' => $this->redirectUrl,
            'scope' => 'activity heartrate profile nutrition settings sleep weight',
        ]);
    }

    public function authorize(array $config): self
    {
        [$code] = $config;

        $response = Http::asForm()->withHeaders([
            'Authorization' => 'Basic '.base64_encode("{$this->clientId}:{$this->clientSecret}"),
        ])->post($this->authTokenUrl, [
            'client_id' => $this->clientId,
            'grant_type' => 'authorization_code',
            'redirect_uri' => route('profile.device-sync.callback', 'fitbit'),
            'code' => $code,
        ]);

        if ($response->successful()) {
            $data = $response->object();

            $tokenExpiresAt = isset($data->expires_at)
                ? now()->addSeconds($data->expires_at)
                : (isset($data->expires_in)
                    ? now()->addSeconds($data->expires_in)
                    : null);

            $this->authResponse = [
                'access_token' => $data->access_token,
                'refresh_token' => $data->refresh_token ?? null,
                'token_expires_at' => $tokenExpiresAt,
                'user_id' => $data->user_id,
            ];
        } else {
            $this->authResponse = [];
        }

        return $this;
    }

    public function verifyWebhook($code): bool
    {
        return $code === $this->fitbitWebhookVerificationCode;
    }

    public function refreshToken($refreshToken = null): array
    {
        $response = Http::asForm()
            ->withHeaders([
                'Authorization' => 'Basic '.base64_encode($this->clientId.':'.$this->clientSecret),
            ])
            ->post($this->authTokenUrl, [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
            ]);

        $data = json_decode($response->body(), true);

        if (isset($data['access_token'])) {
            $profileData = collect($data)->only(['access_token', 'refresh_token'])->toArray();
            $profileData['token_expires_at'] = Carbon::now()->addSeconds($data['expires_in'])->format('Y-m-d H:i:s');

            return $profileData;
        }

        return [];
    }

    public function response(): array
    {
        return $this->authResponse;
    }

    public function setDate($startDate, $endDate = null): self
    {
        if (! is_null($startDate)) {
            [$startDate, $endDate, $dateDays] = $this->daysFromStartEndDate($startDate, $endDate);

            $this->startDate = $startDate;

            $this->endDate = $endDate;

            $this->dateDays = $dateDays;
        }

        return $this;
    }

    public function activities(): Collection
    {
        $data = [];

        if ($this->dateDays) {
            for ($day = 0; $day <= $this->dateDays; $day++) {
                $items = $this->findActivities($this->startDate->addDays($day)->format('Y-m-d'));
                $data = array_merge($data, $items);
            }
        } else {
            $items = $this->findActivities($this->startDate->format('Y-m-d'));
            $data = array_merge($data, $items);
        }

        return collect($data);
    }

    public function processWebhook($url = null): self
    {
        return $this;
    }

    public function formatWebhookRequest($request): Collection
    {
        $notifications = collect($request->_json ? collect($request->_json) : $request->all());

        $items = $notifications->map(function ($notification) {
            [$userId] = explode('-', $notification['subscriptionId']);

            $user = User::find($userId);

            $sourceProfile = null;

            // TODO: Get source profile from App\Models\User.php
            $sourceProfile = $user?->profiles()->whereHas('source', function ($query) {
                return $query->where('short_name', 'fitbit');
            })->first();

            return (object) [
                'user' => $user,
                'date' => $notification['date'],
                'sourceProfile' => $sourceProfile,
                'dataSourceId' => $sourceProfile ? $sourceProfile->data_source_id : null,
                'sourceToken' => $sourceProfile ? $sourceProfile->access_token : null,
                'webhookUrl' => null,
                'extra' => array_merge($notification, ['userId' => $userId, 'source' => 'fitbit']),
            ];
        });

        return $items->filter(function ($item) {
            return $item->user && $item->sourceProfile && $item->sourceToken;
        });
    }

    public function subscribe(int $userId, string $subscriptionId): array
    {
        $subscriptionId = "{$userId}-{$subscriptionId}";

        $response = Http::baseUrl($this->activityBaseUrl)
            ->withToken($this->accessToken)
            ->post("user/-/apiSubscriptions/{$subscriptionId}.json");

        if ($response->successful()) {
            return $response->json();
        }

        return [];
    }

    private function findActivities($date): array
    {
        $response = Http::baseUrl($this->activityBaseUrl)
            ->withToken($this->accessToken)
            ->get(sprintf('user/-/activities/date/%s.json', $date));

        if ($response->successful()) {
            $activities = collect($response->json('activities'));
        } else {
            $activities = collect([]);
        }

        $activities = $activities->map(function ($item) {
            $modality = $this->modality($item['name']);
            $date = $item['startDate'];
            $distance = $item['distance'] * 0.621371;
            $raw_distance = $item['distance'];

            return compact('date', 'distance', 'modality', 'raw_distance');
        });

        // TODO: Figure out how daily steps distance will be calculated.

        $items = $activities->reduce(function ($data, $item) {
            if (! isset($data[$item['modality']])) {
                $data[$item['modality']] = $item;

                return $data;
            }

            $data[$item['modality']]['distance'] += $item['distance'];
            $data[$item['modality']]['raw_distance'] += $item['raw_distance'];

            return $data;
        }, []);

        return collect($items)->values()->toArray();
    }

    private function modality(string $modality): string
    {
        return match ($modality) {
            'Run' => 'run',
            'Walk' => 'walk',
            'Bike', 'Bicycling' => 'bike',
            'Swim' => 'swim',
            'Hike' => 'other',
            default => 'daily_steps',
        };
    }
}
