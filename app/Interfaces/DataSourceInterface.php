<?php

declare(strict_types=1);

namespace App\Interfaces;

use Illuminate\Support\Collection;

interface DataSourceInterface
{
    public function authUrl();

    public function authorize(array $config): self;

    public function refreshToken(?string $refreshToken): array;

    public function activities($responseType = 'data'): Collection;

    public function verifyWebhook(array|string $code): array|bool|int;

    public function setAccessToken(string $accessToken): self;

    public function setAccessTokenSecret(string $accessTokenSecret): self;
}
