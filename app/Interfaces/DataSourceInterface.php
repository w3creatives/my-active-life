<?php

namespace App\Interfaces;

use Illuminate\Support\Collection;

interface DataSourceInterface
{
    public function authUrl();

    public function authorize(array $config): self;

    public function refreshToken(?string $refreshToken): array;

    public function activities(): Collection;

    public function verifyWebhook(string $code): bool|int;

    public function setAccessToken(string $accessToken): self;

    public function setAccessTokenSecret(string $accessTokenSecret): self;
}
