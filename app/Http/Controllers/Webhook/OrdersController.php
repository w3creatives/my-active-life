<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\ShopifyOrder;
use App\Models\ShopifyWebhookEvent;
use App\Services\{
    ShopifyService,
    UserService
};
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Repositories\SopifyRepository;
use App\Services\HubspotService;
use Carbon\Carbon;

class OrdersController extends Controller
{
    protected $shopify;

    public function __construct()
    {
        $this->shopify = new ShopifyService();
    }

    public function getOrders(SopifyRepository $sopifyRepository, UserService $userService, ShopifyService $shopifyService, HubspotService $hubspotService)
    {
        try {
            // Retrieve the last schedule run time or default to a day ago
            $lastRun = Cache::get('shopify_cron_last_run', now()->subDay()->toIso8601String());

            $params = [
                'updated_at_min' => $lastRun
            ];

            $orders = $this->shopify->ordersList($params);
            
            if (isset($orders['orders']) && is_array($orders['orders'])) {
                foreach( $orders['orders'] as $order ) {
                    foreach ($order['line_items'] as $lineItem) {
                       //   dd($lineItem);
                        $productInfo = $shopifyService->fetchProductById($lineItem['product_id']);
            
                        $metafields = [];
                        
                        if($productInfo && $productInfo['metafields']) {
                            foreach( $productInfo['metafields'] as $metafield ) {
                                $metafields[$metafield['key']] = $metafield['value'];
                            }
                        }
                        
                        $lineItem['product_type']  = $productInfo['product_type'];
                        $lineItem['product_tags']  = $productInfo['tags'];
                            
                        $lineItem['meta_fields'] = json_encode($metafields);
                        
                        $sopifyOrder = $sopifyRepository->createOrder($order,$lineItem);
                        
                          if(in_array(strtolower($lineItem['product_type']),['registrations','registration'])) {
                            
                            $user = $userService->createShopifyUser($sopifyOrder, $lineItem['properties'], $metafields);
                        
                            /*if($user){
                        
                                $hubspotStatus = $hubspotService->existsOrCreate($user);
                                
                                $sopifyRepository->updateStatus($user->email,false, $hubspotStatus);
                            }*/
                        }
                        
                        Log::info("Item '{$lineItem['name']}' successfully added to Order #{$order['order_number']}.");
                    }
                }
            }

            // Update the last schedule run time
            Cache::put('shopify_cron_last_run', now()->toIso8601String());
        } catch (\Exception $e) {
            // Log the error
            Log::error('Error fetching Shopify orders: ' . $e->getMessage());
        }
    }
    
    public function orderList(UserService $userService,SopifyRepository $sopifyRepository, ShopifyService $shopifyService, HubspotService $hubspotService,Request $request){
        
        
        $defaultToken = '9dddc24a-09ab-420e-8dfb-feb94728ce73';
        
        if($request->method() == 'POST'){
            $accessToken = $request->get('token');
            
            if($accessToken !== $defaultToken){
                          
                $request->session()->flash('status', 'Invalid token, try again');
                
                return redirect()->back();
            }
            
            $request->session()->put('token', $accessToken);
            
             return redirect('/shopify/orders');
        }
        
         if (!$request->session()->has('token') || $request->session()->get('token') != $defaultToken) {
            return view('login_with_otp');
        }

        
        if($request->get('action') == 'create') {
            $order = ShopifyOrder::find($request->get('id'));
            $data = json_decode($order->properties,true);
            
            $userData = [];
            
            foreach($data as $item) {
                $key = null;
                if(Str::contains($item['name'], 'mail')){
                   $key = 'email';
               } else if(Str::contains($item['name'], 'rstName')){
                   $key = 'first_name';
               } else if(Str::contains($item['name'], 'astName')){
                   $key = 'last_name';
               }
               
               if($key == null) {
                   continue;
               }
               
               $userData[$key] = $item['value'];
            };
            
            
            if(!$userData || !isset($userData['email']) || !$userData['email']) {
                
                $userData = [
                    'email' => $order->email,
                    'first_name' => $order->first_name,
                    'last_name' => $order->last_name
                ];
            }
            
                        
            if($userData){
                $userData['product_sku'] = $order->product_sku;
                
                $user = (object)$userData;
        
                $hubspotStatus = $hubspotService->existsOrCreate($user, true);
                
                $order->hubspot_status = $hubspotStatus;
                $order->save();
                
                if($hubspotStatus == true) {
                    $hubspotCompletedCount = ShopifyOrder::where('order_number',$order->order_number)->where('hubspot_status', true)->count();
                    
                    $totalCount = ShopifyOrder::where('order_number',$order->order_number)->count();
                    
                    if($totalCount == $hubspotCompletedCount) {
                        ShopifyWebhookEvent::where('order_number',$order->order_number)->update(['hubspot_status' => $hubspotStatus]);
                    }
                }
                
                $request->session()->flash('status', $hubspotStatus?'Task was successful!':'Task was unsuccessful!');
            
            return redirect()->back();
            }
            
            
            $request->session()->flash('status', 'Unabled to complete your request');
            
            return redirect()->back();
        }
        
        $orders = ShopifyOrder::whereNull('hubspot_status')->paginate();
            return view('users',compact('orders'));
    }
    
    public function userHubspotVerification(Request $request, HubspotService $hubspotService){
        
        
        //$isProcessRunning = Cache::has('hubspot_process_running');
        /*
        if($isProcessRunning != false) {
            return response()->json([],404);
        }*/
        
        //Cache::put('hubspot_process_running', Carbon::now());
        
        $currentTime = Carbon::now()->addMinutes(5);
        
        $dataStartFrom = Carbon::parse($request->get('date_start','2025-01-04'));

        $orders = ShopifyOrder::where('created_at','<=',$currentTime)
        ->where('order_number','>',274051)
        ->where('created_at','>',$dataStartFrom)->whereNull('hubspot_status')->get();
        
        if(!$orders->count()) {
            return response()->json([],403);
        }
        
        foreach($orders as $order){
            $data = json_decode($order->properties,true);
            
            $userData = [];
            
            foreach($data as $item) {
                $key = null;
                if(Str::contains($item['name'], 'mail')){
                   $key = 'email';
               } else if(Str::contains($item['name'], 'rstName')){
                   $key = 'first_name';
               } else if(Str::contains($item['name'], 'astName')){
                   $key = 'last_name';
               }
               
               if($key == null) {
                   continue;
               }
               
               $userData[$key] = $item['value'];
            };
            
            if(!$userData || !isset($userData['email']) || !$userData['email']) {
                
                $userData = [
                    'email' => $order->email,
                    'first_name' => $order->first_name,
                    'last_name' => $order->last_name
                ];
            }
            
            if(!$userData) {
                continue;
            }
            $userData['product_sku'] = $order->product_sku;
            
            $user = (object)$userData;
    
            $hubspotStatus = $hubspotService->existsOrCreate($user, true);
            
            $order->hubspot_status = $hubspotStatus;
            $order->save();
            
            if($hubspotStatus == true) {
                $hubspotCompletedCount = ShopifyOrder::where('order_number',$order->order_number)->where('hubspot_status', true)->count();
                
                $totalCount = ShopifyOrder::where('order_number',$order->order_number)->count();
                
                if($totalCount == $hubspotCompletedCount) {
                    ShopifyWebhookEvent::where('order_number',$order->order_number)->update(['hubspot_status' => $hubspotStatus]);
                }
            }

        }
        
        //Cache::delete('hubspot_process_running');   
    }
}
