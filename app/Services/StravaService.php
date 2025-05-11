<?php

declare(strict_types=1);

namespace App\Services;

use App\Interfaces\DataSourceInterface;
use App\Traits\CalculateDaysTrait;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

final class StravaService implements DataSourceInterface
{
    use CalculateDaysTrait;

    private string $apiUrl;

    private string $accessToken;

    private string $clientId;

    private string $redirectUrl;

    private string $clientSecret;

    private string $authUrl = 'https://www.strava.com/oauth/authorize';

    private string $authTokenUrl = 'https://www.strava.com/oauth/token';

    private array $authResponse;

    private $startDate;

    private $endDate;

    private $dateDays;

    public function __construct($accessToken = '')
    {
        $this->accessToken = $accessToken;

        $this->apiUrl = config('services.strava.api_url');
        $this->clientId = config('services.strava.client_id');
        $this->redirectUrl = config('services.strava.redirect_url');
        $this->clientSecret = config('services.strava.client_secret');
    }

    public function setDate($startDate, $endDate = null): self
    {
        [$startDate, $endDate, $dateDays] = $this->daysFromStartEndDate($startDate, $endDate);

        $this->startDate = $startDate;

        $this->endDate = $endDate;

        $this->dateDays = $dateDays;

        return $this;
    }

    public function modality(string $modality): string
    {
        return match ($modality) {
            'Run', 'VirtualRun' => 'run',
            'Walk' => 'walk',
            'EBikeRide', 'MountainBikeRide', 'EMountainBikeRide', 'GravelRide', 'Handcycle', 'Ride', 'VirtualRide' => 'bike',
            'Swim' => 'swim',
            'Elliptical', 'Hike', 'StairStepper', 'Snowshoe' => 'other',
            default => 'none',
        };
    }

    public function setAccessToken(string $accessToken): self
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    public function setAccessTokenSecret(string $accessTokenSecret): self
    {
        return $this;
    }

    public function authUrl($state = 'web'): string
    {
        return $this->authUrl.'?'.http_build_query([
            'client_id' => $this->clientId,
            'response_type' => 'code',
            'redirect_uri' => $this->redirectUrl,
            'scope' => 'read,activity:read',
            'approval_prompt' => 'auto',
            'state' => $state,
        ]);
    }

    public function authorize(array $config): self
    {
        [$code] = $config;

        $response = Http::post($this->authTokenUrl, [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
        ]);

        if ($response->successful()) {
            $data = $response->object();

            $tokenExpiresAt = Carbon::parse($data->expires_at)->format('Y-m-d H:i:s');

            $this->authResponse = [
                'access_token' => $data->access_token,
                'refresh_token' => $data->refresh_token ?? null,
                'token_expires_at' => $tokenExpiresAt,
            ];
        } else {
            $this->authResponse = [];
        }

        return $this;
    }

    public function response(): array
    {
        return $this->authResponse;
    }

    public function refreshToken(?string $refreshToken): array
    {
        return [];
    }

    public function activities(): Collection
    {
        $data = [];

        if ($this->dateDays) {
            for ($day = 0; $day <= $this->dateDays; $day++) {

                $startOfDay = $this->startDate->addDays($day)->copy()->startOfDay()->timestamp;
                $endOfDay = $this->startDate->addDays($day)->copy()->endOfDay()->timestamp;

                $items = $this->findActivities($startOfDay, $endOfDay, $data);
                $data = $items;
            }
        } else {
            $startOfDay = $this->startDate->copy()->startOfDay()->timestamp;
            $endOfDay = $this->startDate->copy()->endOfDay()->timestamp;

            $items = $this->findActivities($startOfDay, $endOfDay, $data);
            $data = $items;
        }

        return collect($data)->reject(function ($item) {
            return $item['modality'] === 'none';
        })->values();
    }

    public function verifyWebhook($code): int
    {
        return http_response_code(204);
    }

    private function findActivities($startOfDay, $endOfDay, array $data, int $page = 1): array
    {
        $params = [
            'after' => $startOfDay,
            'before' => $endOfDay,
            'per_page' => 30,
            'page' => $page,
        ];

        $response = Http::withToken($this->accessToken)->get($this->apiUrl.'athlete/activities?'.http_build_query($params));

        if ($response->successful()) {
            $activities = collect($response->json());

            if ($activities->count()) {
                $page++;

                $data = array_merge($data, $activities->toArray());

                return $this->findActivities($startOfDay, $endOfDay, $data, $page);
            }
        }

        $activities = collect($data)->map(function ($activity) {
            if (! isset($activity['start_date'])) {
                return $activity;
            }

            $date = Carbon::parse($activity['start_date'])->format('Y-m-d');
            $distance = round(($activity['distance'] / 1609.344), 3);
            $modality = $this->modality($activity['sport_type']);

            return compact('date', 'distance', 'modality');
        });

        $items = $activities->reduce(function ($data, $item) {
            if (! isset($data[$item['modality']])) {
                $data[$item['modality']] = $item;

                return $data;
            }

            $data[$item['modality']]['distance'] += $item['distance'];

            return $data;
        }, []);

        return collect($items)->values()->toArray();
    }
}
