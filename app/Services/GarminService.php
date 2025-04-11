<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Exception;
use Illuminate\Support\Facades\Log;

class GarminService
{
    private $consumerKey;
    private $consumerSecret;
    private $healthApiUrl = 'https://apis.garmin.com/wellness-api/rest';
    
    public function __construct()
    {
        $this->consumerKey = config('services.garmin.consumer_key');
        $this->consumerSecret = config('services.garmin.consumer_secret');
    }
    
    public function getUserActivities($accessToken, $accessTokenSecret, $startTime, $endTime)
    {
        try {
            $url = $this->healthApiUrl . '/dailies';
            
            $params = [
                'oauth_consumer_key' => $this->consumerKey,
                'oauth_token' => $accessToken,
                'oauth_signature_method' => 'HMAC-SHA1',
                'oauth_timestamp' => time(),
                'oauth_nonce' => $this->generateNonce(),
                'oauth_version' => '1.0',
                'uploadStartTimeInSeconds' => $startTime,
                'uploadEndTimeInSeconds' => $endTime
            ];

            $signature = $this->createSignature('GET', $url, $params, $accessTokenSecret);
            $params['oauth_signature'] = $signature;

            $response = Http::withHeaders([
                'Authorization' => $this->buildAuthorizationHeader($params)
            ])->get($url . '?' . http_build_query($params));
            
            dd($response->json());

            if (!$response->successful()) {
                Log::error('Garmin API Error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return null;
            }

            return $response->json();

        } catch (Exception $e) {
            Log::error('Garmin Service Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new Exception('Failed to fetch Garmin activities: ' . $e->getMessage());
        }
    }

    private function generateNonce()
    {
        return md5(uniqid(rand(), true));
    }

    private function createSignature($method, $url, $params, $tokenSecret)
    {
        ksort($params);
        $paramString = http_build_query($params, '', '&', PHP_QUERY_RFC3986);

        $baseString = strtoupper($method) . '&' .
            rawurlencode($url) . '&' .
            rawurlencode($paramString);

        $signingKey = rawurlencode($this->consumerSecret) . '&' . rawurlencode($tokenSecret);

        return rawurlencode(
            base64_encode(
                hash_hmac('sha1', $baseString, $signingKey, true)
            )
        );
    }

    private function buildAuthorizationHeader($params)
    {
        $headerParams = [];
        foreach ($params as $key => $value) {
            if (strpos($key, 'oauth_') === 0) {
                $headerParams[] = $key . '="' . $value . '"';
            }
        }
        return 'OAuth ' . implode(', ', $headerParams);
    }
}