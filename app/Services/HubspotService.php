<?php

declare(strict_types=1);

namespace App\Services;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

final class HubspotService
{
    private $accessToken;

    private $apiUrl;

    private $endpoint;

    private $hsClient;

    private $httpClient;

    private $hubspotApiUrl = 'https://api.hubapi.com';

    public function __construct()
    {
        $this->accessToken = env('HUBSPOT_ACCESS_TOKEN');
        $this->apiUrl = 'https://api.hubapi.com';
        $this->httpClient = Http::withHeaders([
            'Authorization' => 'Bearer '.env('HUBSPOT_ACCESS_TOKEN'),
            'Content-Type' => 'application/json',
        ]);
    }

    public function emailExistsdd($email)
    {

        try {
            (new Client)->get("{$this->hubspotApiUrl}/crm/v3/objects/contacts/{$email}?idProperty=email", [
                'headers' => [
                    'Authorization' => "Bearer {$this->accessToken}",
                    'Content-Type' => 'application/json',
                ],
            ]);

            return true;

        } catch (Exception $e) {
            return false;
        }
    }

    public function emailExists($email)
    {
        $response = $this->httpClient->get("{$this->hubspotApiUrl}/crm/v3/objects/contacts/{$email}?idProperty=email");

        return $response->ok();

    }

    public function existsOrCreateddd($user)
    {

        /*
        if($this->emailExists($user->email)){
            return true;
        }

        $client = new Client();
        $headers = [
          'Content-Type' => 'application/json',
          'Authorization' => '••••••'
        ];

        $data = json_encode([
            'properties' => [
                'email' => $user->email,
                'firstname' => $user->first_name,
                'lastname' => $user->last_name
            ]
        ]);
        $request = new Request('POST', 'https://api.hubapi.com/crm/v3/objects/contacts', $headers, $data);
        $res = $client->sendAsync($request)->wait();
        echo $res->getBody();

        dd();

        $data = json_encode([
            'properties' => [
                'email' => $user->email,
                'firstname' => $user->first_name,
                'lastname' => $user->last_name
            ]
        ]);

        $response = $this->httpClient->withBody($data)
        ->post("{$this->hubspotApiUrl}/crm/v3/objects/contacts");

        return $response->ok();*/

    }

    public function existsOrCreate($user, $canCreate = false)
    {

        if ($this->emailExists($user->email)) {
            if ($canCreate) {
                $this->updateContactByEmail($user);
            }

            return true;
        }

        if ($canCreate === false) {
            return false;
        }

        $properties = array_merge([
            'email' => $user->email,
            'firstname' => isset($user->first_name) ? $user->first_name : '',
            'lastname' => isset($user->last_name) ? $user->last_name : '',
        ], $this->contactChallengeProperty($user));

        $data = json_encode([
            'properties' => $properties,
        ]);

        $response = $this->httpClient->withBody($data)
            ->post("{$this->hubspotApiUrl}/crm/v3/objects/contacts");

        return $response->created();

    }

    private function hubspotChallenges($key)
    {

        switch ($key) {
            case '2025-Kids-Digital-Tracker':
                return 'Run the Year Kids 2025';
                break;
            case 'KS25':
            case 'MP25':
            case 'SLP-2025':
            case 'EN25':
                return 'Run the Year 2025';
                break;
            case 'AMBasic':
            case 'AMDeluxe':
            case 'AMGALXS':
            case 'AMGALS':
            case 'AMGALM':
            case 'AMGALL':
            case 'AMGALXL':
            case 'AMGALXXL':
            case 'AMGALXXXL':
            case 'AMGAUXS':
            case 'AMGAUS':
            case 'AMGAUM':
            case 'AMGAUL':
            case 'AMGAUXL':
            case 'AMGAUXXL':
            case 'AMGAUXXL':
                return 'Amerithon Challenge';
                break;
            case '2025-March-Streaker':
                return '2025 March Streaker';
                break;
            case '2025-April-Streaker':
                return '2025 April Streaker';
                break;
            case '2025-May-Streaker':
                return '2025 May Streaker';
                break;
            case '2025-2pack-Streaker':
                return '2025 April Streaker;2025 May Streaker';
                break;
            case '2025-Streaker Bundle':
                return '2025 March Streaker;2025 April Streaker;2025 May Streaker';
                break;
            case 'HeroGIA':
            case 'HeroBasic':
                return 'Hero June 2025';
                break;
        }

        return null;
    }

    private function contactChallengeProperty($user)
    {
        if (! isset($user->product_sku)) {
            return [];
        }

        $challenges = $this->hubspotChallenges($user->product_sku);

        if (! $challenges) {
            return [];
        }

        return compact('challenges');
    }

    private function updateContactByEmail($user)
    {

        $properties = $this->contactChallengeProperty($user);

        if (! $properties) {
            return false;
        }

        $properties['challenges'] = ';'.$properties['challenges'];

        $data = json_encode([
            'properties' => $properties,
        ]);

        $response = $this->httpClient->withBody($data)->patch("{$this->hubspotApiUrl}/crm/v3/objects/contacts/{$user->email}?idProperty=email");

        return $response->created();
    }

    private function response()
    {
        try {
            $response = $this->hsClient->get("{$this->apiUrl}{$this->endpoint}", [
                'headers' => [
                    'Authorization' => "Bearer {$this->accessToken}",
                    'Content-Type' => 'application/json',
                ],
            ]);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
