<?php

declare(strict_types=1);
/**
 * @Deprecated
 * I will be removed once changes are migrated
 */

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

final class FitbitAuthController extends Controller
{
    // Fitbit API credentials
    private $clientId;

    private $clientSecret;

    private $redirectUri;

    public function __construct()
    {
        // Fitbit API credentials
        $this->clientId = env('FITBIT_CLIENT_ID');
        $this->clientSecret = env('FITBIT_CLIENT_SECRET');
        $this->redirectUri = env('FITBIT_REDIRECT_URI', route('fitbit.callback'));
    }

    /**
     * Redirect the user to Fitbit's OAuth 2.0 authorization page.
     */
    public function redirectToFitbit(Request $request, $state = 'web')
    {
        session(['loggedin_user_id' => $request->get('uid')]);

        $authorizationUrl = 'https://www.fitbit.com/oauth2/authorize';
        $queryParams = http_build_query([
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'scope' => 'activity sleep nutrition settings profile weight', // Adjust scopes as needed
            'expires_in' => '86400',
            'state' => $state,
        ]);

        return redirect("{$authorizationUrl}?{$queryParams}");
    }

    /**
     * Handle Fitbit OAuth 2.0 callback and exchange authorization code for tokens.
     */
    public function handleCallback(Request $request)
    {
        $isApp = $request->get('state') === 'app';

        if ($request->has('error')) {
            return response()->json(['error' => $request->get('error')]);
        }

        $authorizationCode = $request->get('code');

        if (! $authorizationCode) {
            return response()->json(['error' => 'Authorization code missing.']);
        }

        $tokenUrl = 'https://api.fitbit.com/oauth2/token';

        $response = Http::asForm()->withHeaders([
            'Authorization' => 'Basic '.base64_encode("{$this->clientId}:{$this->clientSecret}"),
        ])->post($tokenUrl, [
            'client_id' => $this->clientId,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->redirectUri,
            'code' => $authorizationCode,
        ]);

        if ($response->failed()) {
            return response()->json(['error' => 'Failed to exchange authorization code for tokens.']);
        }

        $tokens = $response->json();

        $this->subscribe($tokens['access_token'], session('loggedin_user_id'), $tokens['user_id']);

        if ($isApp) {
            if ($response === false) {
                return response()->json(['message' => 'Unabled to complete your request'], 403);
            }

            // "rte://settings/fitbit/${$tokens['access_token']}/${$tokens['refresh_token']}/${$tokens['expires_in']}/${$tokens['user_id']}"
            // rte://settings/fitbit/${access_token}/${refresh_token}/${token_expires_at}/${access_token_secret}

            return redirect(sprintf('rte://settings/%s/%s/%s/%s/%s/1', $tokens['access_token'], $tokens['refresh_token'], $tokens['expires_in'], $tokens['user_id'], 'fitbit'));

            return redirect("rte://settings/${$tokens['access_token']}/${$tokens['refresh_token']}/${$tokens['expires_in']}/${$tokens['user_id']}");
        }

        // dd($response,$tokens);
        // Save tokens in your database or session
        // Example: Save in session for temporary use
        session([
            'fitbit_access_token' => $tokens['access_token'],
            'fitbit_refresh_token' => $tokens['refresh_token'],
        ]);

        return response()->json(['message' => 'Fitbit authentication successful!', 'tokens' => $tokens]);
    }

    /**
     * Refresh Fitbit access token.
     */
    public function refreshToken()
    {
        $refreshToken = session('fitbit_refresh_token');

        if (! $refreshToken) {
            return response()->json(['error' => 'Refresh token missing.']);
        }

        $tokenUrl = 'https://api.fitbit.com/oauth2/token';

        $response = Http::asForm()->withHeaders([
            'Authorization' => 'Basic '.base64_encode("{$this->clientId}:{$this->clientSecret}"),
        ])->post($tokenUrl, [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
        ]);

        if ($response->failed()) {
            return response()->json(['error' => 'Failed to refresh access token.']);
        }

        $tokens = $response->json();

        // Update tokens in your storage
        session([
            'fitbit_access_token' => $tokens['access_token'],
            'fitbit_refresh_token' => $tokens['refresh_token'],
        ]);

        return response()->json(['message' => 'Token refreshed successfully!', 'tokens' => $tokens]);
    }

    public function subscribe($accessToken, int $userId, string $subscriptionId): array
    {
        $subscriptionId = "{$userId}-{$subscriptionId}";

        $response = Http::baseUrl('https://api.fitbit.com/1/')
            ->withToken($accessToken)
            ->post("user/-/apiSubscriptions/{$subscriptionId}.json");

        if ($response->successful()) {
            return $response->json();
        }

        return [];
    }
}
