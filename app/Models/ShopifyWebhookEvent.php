<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopifyWebhookEvent extends Model
{
    use HasFactory;
    
    protected $fillable = [
            'order_number',
               'first_name',
        'last_name',
        'email',
        'customer_id',
        'webhook_status',
        'tracker_status',
        'hubspot_status',
    ];
}
