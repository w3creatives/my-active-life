<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Interfaces\DataSourceInterface;
use Illuminate\Http\Request;

class TrackerWebhooksController extends Controller
{
    private $tracker;

    public function __construct()
    {
        $this->tracker = app(DataSourceInterface::class);
    }

    public function verifyWebhook(Request $request, $sourceSlug = 'fitbit')
    {
        return $this->tracker->get($sourceSlug)->verifyWebhook($request->get('verify'));
    }

    
}
