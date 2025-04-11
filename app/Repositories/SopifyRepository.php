<?php

namespace App\Repositories;

use App\Models\ShopifyOrder;
use App\Models\ShopifyWebhookEvent;
use Carbon\Carbon;
use Illuminate\Support\Str;

class SopifyRepository
{
    
    public function createOrder($order, $lineItem){
        
        $hasOrder = ShopifyOrder::where('order_number', $order['order_number'])->where('product_id', $lineItem['product_id'])->count();
            
        if($hasOrder){
            return false;
        }
            
        return ShopifyOrder::create([
            'order_id'      => $order['id'],
            'order_number'  => $order['order_number'],
            'first_name'         => $order['customer']['first_name'],
            'last_name'         => $order['customer']['last_name'],
            'email'         => $order['email'],
            'customer_id'   => $order['customer']['id'] ?? null,
            'total_price'   => $order['total_price'],
            'product_id'    => $lineItem['product_id'],
            'product_name'  => $lineItem['name'],
            'quantity'      => $lineItem['quantity'],
            'product_sku'   => $lineItem['sku'] ?? null,
            'variant_id'    => $lineItem['variant_id'] ?? null,
            'properties'    => json_encode($lineItem['properties'] ?? []),
            'product_type'  => $lineItem['product_type'],
            'product_tags'  => $lineItem['product_tags'],
            'meta_fields'   => $lineItem['meta_fields'],
            'hubspot_status' => null
        ]);
    }
    
    public function updateStatus($email, $isTracker = false, $columnStatus = true){
        
        $column = $isTracker?'tracker_status':'hubspot_status';
        
        $items = ShopifyOrder::where([$column => false])->get();
        
        $metaEmail = null;
        
        $items = $items->filter(function($item) use($email,$metaEmail){
            $properties = json_decode($item->properties,true);
            
            foreach($properties as $row) {
               
                if(Str::contains($row['name'], 'mail')){
                    $metaEmail = $row['value'];
                   break;
               }
            }
            
            if(!$metaEmail){
                $metaEmail = $item->email;
            }
            
            return $metaEmail == $email;
        });
        
        $orderIds = [];
        
        foreach($items as $item) {
            $item->fill([$column => $columnStatus]);
            
            $orderIds[$item->order_number] = $item->order_id;
        }
        
        if(!$columnStatus) {
            return false;
        }
        
        foreach($orderIds as $orderNumber => $orderId) {
            $trackerCompletedCount = ShopifyOrder::where('order_number',$orderNumber)->where('tracker_status', true)->count();
            $hubspotCompletedCount = ShopifyOrder::where('order_number',$orderNumber)->where('hubspot_status', true)->count();
            
            $totalCount = ShopifyOrder::where('order_number',$orderNumber)->count();
            
            if($totalCount == $trackerCompletedCount) {
                ShopifyWebhookEvent::where('order_number',$orderNumber)->update(['tracker_status' => true]);
            }
            
            if($totalCount == $hubspotCompletedCount) {
                  ShopifyWebhookEvent::where('order_number',$orderNumber)->update(['hubspot_status' => true]);
            }
        }  
    }
}