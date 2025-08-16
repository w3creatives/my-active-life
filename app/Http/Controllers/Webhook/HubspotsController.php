<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Http\Request;

final class HubspotsController extends Controller
{
    public function __construct()
    {
        $this->hsAccessToken = env('HUBSPOT_ACCESS_TOKEN');
        $this->hsApiUrl = 'https://api.hubapi.com';
        $this->hsClient = new Client();
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
                ],
            ]);

            return response()->json([], 200);
        } catch (Exception $e) {
            return response()->json([], 404);
        }
    }
}
