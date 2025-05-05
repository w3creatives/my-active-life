<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Inertia;

final class DeviceSyncController extends Controller
{
    private $tracker;

    public function __construct()
    {
        $this->tracker = app(\App\Interfaces\DataSource::class);
    }

    public function index()
    {
        // $garminUrl = $this->tracker->get('garmin')->authUrl();
        $fitbitUrl = $this->tracker->get('fitbit')->authUrl();
        $stravaUrl = $this->tracker->get('strava')->authUrl();

        $garminUrl = '';

        return Inertia::render('settings/device-sync', compact('garminUrl', 'fitbitUrl', 'stravaUrl'));
    }
}
