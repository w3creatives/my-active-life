<?php

namespace App\Interfaces;

interface DataSourceInterface
{
    public function authUrl();

    public function authorize($code);

    public function refreshToken($refreshtoken);

    public function activities();

    public function verifyWebhook($code);

    public function setAccessToken($accessToken);

    public function setAccessTokenSecret($accessTokenSecret);
}
