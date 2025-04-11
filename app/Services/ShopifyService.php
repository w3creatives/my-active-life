<?php

namespace App\Services;

use Shopify\Context;
use Shopify\Clients\Rest;
use Illuminate\Support\Facades\Log;
use Shopify\Auth\FileSessionStorage;

class ShopifyService
{
    protected $store;
    protected $accessToken;
    protected $shopifyClient;

    public function __construct()
    {
        $this->store = config('services.shopify.store');
        $this->accessToken = config('services.shopify.access_token');

        Context::initialize(
            apiKey: config('services.shopify.api_key'),
            apiSecretKey: config('services.shopify.api_secret'),
            scopes: config('services.shopify.scope'),
            hostName: "Sync",
            sessionStorage: new FileSessionStorage('/tmp/phpSessionStprage'),
            apiVersion: config('services.shopify.api_version'),
            isEmbeddedApp: true,
            isPrivateApp: false,
        );

        $this->shopifyClient = new Rest($this->store, $this->accessToken);
    }

    public function ordersList(array $query = [], array $header = [])
    {
        $response = $this->shopifyClient->get('orders', $header, $query);
        return $response->getDecodedBody();
    }

    public function getProducts(array $query = [], array $header = [])
    {
        $response = $this->shopifyClient->get("products", $header, $query);
        return $response->getDecodedBody();
    }

    public function fetchProductById(string $productId = "")
    {
        try {
            // Fetch product details
            $response = $this->shopifyClient->get("products/{$productId}");
            //Log::warning($response->getDecodedBody());
            $product = $response->getDecodedBody()['product'];

            // Fetch metafields for the product
            $metafieldsResponse = $this->shopifyClient->get("products/{$productId}/metafields");
            $product['metafields'] = $metafieldsResponse->getDecodedBody()['metafields'];
 //Log::warning($product);
            return $product;
        } catch (\Exception $e) {
            if ($e instanceof \GuzzleHttp\Exception\ClientException && $e->getCode() === 404) {
                Log::warning("Product ID {$productId} not found or no collections/metafields assigned.");
            } else {
                Log::error("Error fetching product, collections, and metafields for Product ID {$productId}: {$e->getMessage()}");
            }
            return null;
        }
    }
}