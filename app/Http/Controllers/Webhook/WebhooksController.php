<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\ShopifyWebhookEvent;
use App\Repositories\SopifyRepository;
use App\Services\HubspotService;
use App\Services\ShopifyService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class WebhooksController extends Controller
{
    public function handleOrderCreation(Request $request, SopifyRepository $sopifyRepository, UserService $userService, ShopifyService $shopifyService, HubspotService $hubspotService)
    {
        // Verify webhook signature
        $hmacHeader = $request->header('X-Shopify-Hmac-SHA256');
        $data = $request->getContent();
        $calculatedHmac = base64_encode(hash_hmac('sha256', $data, config('services.shopify.webhook_secret'), true));

        if (! hash_equals($hmacHeader, $calculatedHmac)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $orderData = $request->all();

        if (ShopifyWebhookEvent::where('order_number', $orderData['order_number'])->where('webhook_status', 1)->first()) {
            return response()->json(['message' => "Webhook event already triggered for order #{$orderData['order_number']}"]);
        }

        // Add webhook event to database with 0 status.
        $webhookEvent = ShopifyWebhookEvent::updateOrCreate([
            'order_number' => $orderData['order_number'],
            'email' => $orderData['email'] ?? null,
            'first_name' => $orderData['customer']['first_name'] ?? null,
            'last_name' => $orderData['customer']['last_name'] ?? null,
            'customer_id' => $orderData['customer']['id'] ?? null,
        ]);

        // Insert Order details in shopify_orders table.
        foreach ($orderData['line_items'] as $lineItem) {
            // Log::warning($lineItem);
            $productInfo = $shopifyService->fetchProductById($lineItem['product_id']);
            // dd($productInfo);
            $metafields = [];

            if ($productInfo && $productInfo['metafields']) {
                foreach ($productInfo['metafields'] as $metafield) {
                    $metafields[$metafield['key']] = $metafield['value'];
                }
            }

            $lineItem['product_type'] = $productInfo['product_type'];
            $lineItem['product_tags'] = $productInfo['tags'];

            $lineItem['meta_fields'] = json_encode($metafields);

            $sopifyOrder = $sopifyRepository->createOrder($orderData, $lineItem);

            if (in_array(mb_strtolower($lineItem['product_type']), ['registrations', 'registration'])) {

                $user = $userService->createShopifyUser($sopifyOrder, $lineItem['properties'], $metafields);
                /*
                if($user){

                    $hubspotStatus = $hubspotService->existsOrCreate($user);

                    $sopifyRepository->updateStatus($user->email,false, $hubspotStatus);
                }*/
            }
        }

        $webhookEvent->update(['webhook_status' => 1]);

        return response()->json(['message' => 'Order processed successfully']);
    }
}
