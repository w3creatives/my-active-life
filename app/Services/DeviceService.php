<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class DeviceService
{
    private $logChannel = 'device';

    public function revoke($profile)
    {
        switch ($profile->source->short_name) {
            case 'fitbit':
                return $this->revokeFitbit($profile);
                break;
            case 'garmin':
                return $this->revokeGarmin($profile);
                break;
            case 'strava':
                return $this->revokeStrava($profile);
                break;
            default:
                return false;
                break;
        }
    }

    private function logger($profile, $message, $response)
    {

        Log::channel($this->logChannel)->info($message, [
            'userId' => $profile->user_id,
            'isSuccessful' => $response->successful(),
            'response' => $response->body(),
        ]);

    }

    private function revokeFitbit($profile)
    {
        $accessToken = $profile->access_token;

        $clientId = config('services.fitbit.client_id');
        $clientSecret = config('services.fitbit.client_secret');

        $response = Http::withHeaders([
            'Authorization' => 'Basic '.base64_encode($clientId.':'.$clientSecret),
            'Content-Type' => 'application/x-www-form-urlencoded',
        ])->asForm()
            ->post('https://api.fitbit.com/oauth2/revoke', [
                'token' => $accessToken,
            ]);

        $this->logger($profile, 'Fitbit Revoked', $response);

        return $response->successful();
    }

    private function revokeStrava($profile)
    {
        $accessToken = $profile->access_token;

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$accessToken,
        ])->post('https://www.strava.com/oauth/deauthorize');

        $this->logger($profile, 'Strava Revoked', $response);

        return $response->successful();
    }

    private function revokeGarmin($profile)
    {
        $accessToken = $profile->access_token;
        $accessTokenSecret = $profile->access_token_secret;

        $consumerKey = config('services.garmin.consumer_key');

        $url = 'https://apis.garmin.com/wellness-api/rest/user/registration';

        $oauth_params = [
            'oauth_consumer_key' => $consumerKey,
            'oauth_token' => $accessToken,
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => time(),
            'oauth_nonce' => uniqid(),
            'oauth_version' => '1.0',
        ];

        $signature = $this->generateOAuthSignature('DELETE', $url, $oauth_params, $accessTokenSecret);
        $oauth_params['oauth_signature'] = $signature;

        $auth_header = 'OAuth '.http_build_query($oauth_params, '', ', ');

        $response = Http::withHeaders([
            'Authorization' => $auth_header,
        ])->delete($url);

        $this->logger($profile, 'Garmin Revoked', $response);

        return $response->successful();
    }

    /**
     * Generate OAuth signature for Garmin
     */
    private function generateOAuthSignature(string $method, string $url, array $params, string $token_secret): string
    {
        $consumerSecret = config('services.garmin.consumer_secret');

        ksort($params);
        $param_string = http_build_query($params, '', '&', PHP_QUERY_RFC3986);

        $base_string = mb_strtoupper($method).'&'.
            rawurlencode($url).'&'.
            rawurlencode($param_string);

        $signing_key = rawurlencode($consumerSecret).'&'.rawurlencode($token_secret);

        return base64_encode(hash_hmac('sha1', $base_string, $signing_key, true));
    }
}
