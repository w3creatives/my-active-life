<?php

namespace App\Interfaces;

interface DataSource
{

    public function authUrl();

    public function authorize($code);

    public function refreshToken($refreshtoken);

    public function activities();

    public function verifyWebhook();

    public function setAccessToken($accessToken);
    
    public function setAccessTokenSecret($accessTokenSecret);
}
