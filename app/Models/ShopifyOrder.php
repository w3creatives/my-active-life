<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopifyOrder extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'order_id',
        'order_number',
        'first_name',
        'last_name',
        'email',
        'customer_id',
        'total_price',
        'product_id',
        'product_name',
        'product_type',
        'product_tags',
        'meta_fields',
        'quantity',
        'product_sku',
        'variant_id',
        'properties',
        'tracker_status',
        'hubspot_status'
    ];
}
