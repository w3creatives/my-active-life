<?php
/**
 * @Deprecated
 * I will be removed once changes are migrated
 */
namespace App\Http\Controllers\Webhook;

use App\Services\EventService;
use App\Services\GarminService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use App\Models\DataSourceProfile;
use Illuminate\Support\Facades\Log;

class GarminAuthController extends Controller
{
    private $consumerKey;
    private $consumerSecret;
    private $baseUrl;
    private $callbackUrl;

    public function __construct()
    {
        $this->consumerKey = config('services.garmin.consumer_key');
        $this->consumerSecret = config('services.garmin.consumer_secret');
        $this->baseUrl = 'https://connectapi.garmin.com/oauth-service/oauth/';
        $this->callbackUrl = route('garmin.callback');
    }

    /**
     * Initialize OAuth process and get request token
     */
    public function redirectToGarmin($state = 'web')
    {
        session(['state' => $state]);

        $timestamp = time();
        $nonce = Str::random(32);

        // Create signature base string
        $params = [
            'oauth_consumer_key' => $this->consumerKey,
            'oauth_nonce' => $nonce,
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => $timestamp,
            'oauth_version' => '1.0'
        ];

        ksort($params);

        $baseString = 'POST&' . urlencode($this->baseUrl . 'request_token') . '&' . urlencode(http_build_query($params));

        // Generate signature
        $signingKey = $this->consumerSecret . '&';
        $signature = base64_encode(hash_hmac('sha1', $baseString, $signingKey, true));

        // Create authorization header
        $header = 'OAuth ' .
            'oauth_consumer_key="' . urlencode($this->consumerKey) . '", ' .
            'oauth_nonce="' . urlencode($nonce) . '", ' .
            'oauth_signature="' . urlencode($signature) . '", ' .
            'oauth_signature_method="HMAC-SHA1", ' .
            'oauth_timestamp="' . $timestamp . '", ' .
            'oauth_version="1.0"';

        try {
            $response = Http::withHeaders(['Authorization' => $header])->post($this->baseUrl . 'request_token');

            parse_str($response->body(), $result);

            if (isset($result['oauth_token']) && isset($result['oauth_token_secret'])) {
                // Store token secret in session for later use
                Session::put('garmin_token_secret', $result['oauth_token_secret']);

                // Redirect to Garmin authorization page
                return redirect()->away(
                    'https://connect.garmin.com/oauthConfirm?' .
                    http_build_query([
                        'oauth_token' => $result['oauth_token'],
                        'oauth_callback' => $this->callbackUrl,
                    ])
                );
            }

            throw new \Exception('Failed to get request token');
        } catch (\Exception $e) {
            Log::error("Failed to connect to Garmin: ". $e->getMessage());
        }
    }

    /**
     * Handle callback from Garmin and exchange request token for access token
     */
    public function handleCallback(Request $request)
    {
        $isApp = $request->session()->get('state') == 'app';

        if (!$request->has(['oauth_token', 'oauth_verifier'])) {
            Log::error("Authorization failed");
        }

        $timestamp = time();
        $nonce = Str::random(32);

        // Create signature base string for access token request
        $params = [
            'oauth_consumer_key' => $this->consumerKey,
            'oauth_token' => $request->oauth_token,
            'oauth_nonce' => $nonce,
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => $timestamp,
            'oauth_verifier' => $request->oauth_verifier,
            'oauth_version' => '1.0',
        ];

        ksort($params);

        $baseString = 'POST&' . urlencode($this->baseUrl . 'access_token') . '&' .
            urlencode(http_build_query($params));

        // Generate signature using both consumer secret and request token secret
        $signingKey = $this->consumerSecret . '&' . Session::get('garmin_token_secret');
        $signature = base64_encode(hash_hmac('sha1', $baseString, $signingKey, true));

        // Create authorization header
        $header = 'OAuth ' .
            'oauth_consumer_key="' . urlencode($this->consumerKey) . '", ' .
            'oauth_token="' . urlencode($request->oauth_token) . '", ' .
            'oauth_nonce="' . urlencode($nonce) . '", ' .
            'oauth_signature="' . urlencode($signature) . '", ' .
            'oauth_signature_method="HMAC-SHA1", ' .
            'oauth_timestamp="' . $timestamp . '", ' .
            'oauth_verifier="' . urlencode($request->oauth_verifier) . '", ' .
            'oauth_version="1.0"';

        try {
            $response = Http::withHeaders(['Authorization' => $header])
                ->post($this->baseUrl . 'access_token');

            parse_str($response->body(), $result);

            if (isset($result['oauth_token']) && isset($result['oauth_token_secret'])) {
                // Get user ID
                $userId = $this->getUserId($result['oauth_token'], $result['oauth_token_secret']);

                // Log::info("Garmin User ID : {$userId}");
                // Log::info("Successfully connected to Garmin");

                if ($isApp) {
                    return redirect(sprintf("rte://settings/%s/%s/%s/%s/%s/1", $result['oauth_token'], 'NULL', 'NULL', $result['oauth_token_secret'], 'garmin'));
                }

                // Save tokens in your database or session
                session([
                    'garmin_access_token' => $result['oauth_token'],
                    'garmin_token_secret' => $result['oauth_token_secret'],
                ]);

                $tokens = [ 'token' => $result['oauth_token'], 'token_secret' => $result['oauth_token_secret'] ];

                return response()->json([ 'message' => 'Garmin authentication successful!', 'tokens' => $tokens ]);
            }

            // throw new \Exception('Failed to get access token');
        } catch (\Exception $e) {
            Log::error("Failed to complete authorization: {$e->getMessage()}");
        }


        /**
        if ($request->has('error')) {
        return response()->json(['error' => $request->get('error')], 400);
        }

        $oauthToken = $request->get('oauth_token');
        $oauthVerifier = $request->get('oauth_verifier');

        if (!$oauthToken || !$oauthVerifier) {
        return response()->json(['error' => 'Authorization token or verifier missing.'], 400);
        }

        $accessTokenUrl = "https://connectapi.garmin.com/oauth-service/oauth/access_token";
        $oauthNonce = bin2hex(random_bytes(16));
        $oauthTimestamp = time();

        $oauthParams = [
        'oauth_consumer_key' => $this->consumerKey,
        'oauth_token' => $oauthToken,
        'oauth_signature_method' => 'HMAC-SHA1',
        'oauth_timestamp' => $oauthTimestamp,
        'oauth_nonce' => $oauthNonce,
        'oauth_version' => '1.0',
        'oauth_verifier' => $oauthVerifier
        ];

        ksort($oauthParams);

        // POST&https%3A%2F%2Fconnectapi.garmin.com%2Foauth-service%2Foauth%2Faccess_token&oauth_consumer_key%3Da0fe50a6-7969-4a2f-a891-a0863455c801%26oauth_nonce%3Dbeaa5a2ffbb10c00c9f7b93175027084%26oauth_signature_method%3DHMAC-SHA1%26oauth_timestamp%3D1739899027%26oauth_token%3D18f94f3c-68eb-4b52-9ed0-b190421a08ea%26oauth_verifier%3DV02kUnkQ9W%26oauth_version%3D1.0
        $baseString = 'POST&' . rawurlencode($accessTokenUrl) . '&' . rawurlencode(http_build_query($oauthParams, '', '&'));

        $signingKey = rawurlencode($this->consumerSecret) . '&';
        dd($signingKey);
        $oauthParams['oauth_signature'] = base64_encode(hash_hmac('sha1', $baseString, $signingKey, true));

        $authHeader = 'OAuth ' . urldecode(http_build_query($oauthParams, '', ', '));

        Log::info('Authorization Header: ' . $authHeader);

        $response = Http::asForm()->withHeaders(['Authorization' => $authHeader])->post($accessTokenUrl);

        Log::info('Garmin Access Token Response: ' . $response->body());

        parse_str($response->body(), $tokens);

        if (!isset($tokens['oauth_token'])) {
        return response()->json(['error' => 'Failed to get access token.'], 400);
        }

        if ($isApp) {
        return redirect(sprintf("rte://settings/garmin/%s/%s", $tokens['oauth_token'], $tokens['oauth_token_secret']));
        }

        session([
        'garmin_access_token' => $tokens['oauth_token'],
        'garmin_token_secret' => $tokens['oauth_token_secret'],
        ]);

        return response()->json(['message' => 'Garmin authentication successful!', 'tokens' => $tokens], 200);
         */
    }

    /**
     * Get Garmin User ID using access token
     */
    private function getUserId($accessToken, $accessTokenSecret)
    {
        $timestamp = time();
        $nonce = Str::random(32);

        $params = [
            'oauth_consumer_key' => $this->consumerKey,
            'oauth_token' => $accessToken,
            'oauth_nonce' => $nonce,
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => $timestamp,
            'oauth_version' => '1.0',
        ];

        ksort($params);

        $baseString = 'GET&' . urlencode('https://apis.garmin.com/wellness-api/rest/user/id') . '&' .
            urlencode(http_build_query($params));

        $signingKey = $this->consumerSecret . '&' . $accessTokenSecret;
        $signature = base64_encode(hash_hmac('sha1', $baseString, $signingKey, true));

        $header = 'OAuth ' .
            'oauth_consumer_key="' . urlencode($this->consumerKey) . '", ' .
            'oauth_token="' . urlencode($accessToken) . '", ' .
            'oauth_nonce="' . urlencode($nonce) . '", ' .
            'oauth_signature="' . urlencode($signature) . '", ' .
            'oauth_signature_method="HMAC-SHA1", ' .
            'oauth_timestamp="' . $timestamp . '", ' .
            'oauth_version="1.0"';

        $response = Http::withHeaders(['Authorization' => $header])
            ->get('https://apis.garmin.com/wellness-api/rest/user/id');

        $result = json_decode($response->body(), true);
        return $result['userId'] ?? null;
    }

    /**
     * Handle webhook from Garmin
     *
     * @param Request $request The webhook request
     * @param EventService $eventService The event service
     * @param GarminService $garminService The Garmin service
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleWebhook(Request $request, EventService $eventService, GarminService $garminService)
    {
        /*
         * Data received from WebHook
         *
         * array (
         *    'activities' =>
         *    array (
         *      0 =>
         *      array (
         *        'userId' => '98a2746a-b1f1-4c16-8e89-37fdb0c5fc9f',
         *        'userAccessToken' => 'c07bcb32-2c0a-4a59-bd00-6169b66927db',
         *        'uploadStartTimeInSeconds' => 1745058977,
         *        'uploadEndTimeInSeconds' => 1745058977,
         *        'callbackURL' => 'https://apis.garmin.com/wellness-api/rest/activities?uploadStartTimeInSeconds=1745058977&uploadEndTimeInSeconds=1745058977',
         *      ),
         *    ),
         *  )
         */

        Log::info("Webhook Data Received for Garmin: " . json_encode($request->all()));
        // Process the webhook data using the GarminService
        $result = $garminService->processWebhookData($request, $eventService);

        // // Return the appropriate response
        // return response()->json([
        //     'status' => $result['status'],
        //     'message' => $result['message'],
        //     'activities_processed' => $result['activities_processed'] ?? 0
        // ], $result['code']);
    }

    public function handleGarminDeregistrations(Request $request){

        Log::channel('device')->info("GARMIN Deregistrations:",['message' => 'Deregistrations requested', 'data' => $request->all()]);

        if(!isset($request->deregistrations)) {
            Log::channel('device')->info("GARMIN Deregistrations:",['message' => 'Deregistrations missing data', 'data' => $request->all()]);
            return response()->noContent();
        }

        foreach($request->deregistrations as $deregistration) {

            $profile = DataSourceProfile::where('access_token', $deregistration['userAccessToken'])->whereHas('source', function($query){
                return $query->where('short_name','garmin');
            })
                ->limit(1)
                ->first();

            if(!$profile) {
                Log::channel('device')->info("GARMIN Deregistrations:",['message' => 'Access token not found', 'data' => $request->all()]);
                continue;
            }

            $profile->delete();

            $profile->user->logSourceDisconnected(['data_source_id' => $profile->data_source_id, 'action_source' => 'webhook','action' => 'ACCESS_REVOKED']);

            Log::channel('device')->info("GARMIN Deregistrations:",['message' => 'Access revoked', 'data' => $request->all(),'userId' => $profile->user_id]);

        }

        return response()->noContent();

    }
}
