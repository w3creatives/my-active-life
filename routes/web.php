<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use GuzzleHttp\Client;

use App\Http\Controllers\{
    TrackerLoginsController,
    FitbitAuthController,
    GarminAuthController
};

use App\Http\Controllers\Shopify\{
    WebhooksController,
    OrdersController
};
use App\Http\Controllers\Webhook\{
    TrackersController,
    TestTrackersController,
    HubspotsController,
    UserActivitiesController
};

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');
Route::get('test', function () {
    $tracker = app(\App\Interfaces\DataSource::class);

    //$accesToken = "eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiIyMkNLRzIiLCJzdWIiOiJDOVZHV1AiLCJpc3MiOiJGaXRiaXQiLCJ0eXAiOiJhY2Nlc3NfdG9rZW4iLCJzY29wZXMiOiJyYWN0IHJzZXQgcndlaSBybnV0IHJwcm8gcnNsZSIsImV4cCI6MTc0NTg4NzEyMCwiaWF0IjoxNzQ1ODU4MzIwfQ.FbY5-5T7mN1vtRaXg6lW0MNA3LBwnZjEx3xk0Qmizig";
    //return ($tracker->get('fitbit')->setAccessToken($accesToken)->setDate('2025-01-12','2025-01-18')->activities());

    $accesToken = "a34e18a6-2473-42a4-b8e6-bb8df5061fd8";

    dd($tracker->get('garmin')->authUrl());
    return ($tracker->get('garmin')->setAccessToken($accesToken)
    ->setAccessTokenSecret("1N0baQBO247cGPk8iKd0w4KvEVnPA4HZm96")
    ->setRequestType('upload')
    ->setDate('2025-04-13','2025-04-20')->activities());
});
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/webhook.php';
