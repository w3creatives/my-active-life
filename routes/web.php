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

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/webhook.php';
