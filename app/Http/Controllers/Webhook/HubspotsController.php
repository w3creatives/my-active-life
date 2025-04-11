<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use GuzzleHttp\Client;
use Exception;

class HubspotsController extends Controller
{
    public function __construct()
    {
        $this->hsAccessToken    = env('HUBSPOT_ACCESS_TOKEN');
        $this->hsApiUrl         = 'https://api.hubapi.com';
        $this->hsClient         = new Client();
    }

    public function verifyUserEmail(Request $request)
    {
        return $this->makeCall("/crm/v3/objects/contacts/{$request['email']}?idProperty=email");
        
    }

    private function makeCall(string $endpoint)
    {
        try {
            $response = $this->hsClient->get("{$this->hsApiUrl}{$endpoint}", [
                'headers' => [
                    'Authorization' => "Bearer {$this->hsAccessToken}",
                    'Content-Type' => 'application/json',
                ]
            ]);

            return response()->json([],200);
        } catch (Exception $e) {
            return response()->json([],404);
        }
    }
}
