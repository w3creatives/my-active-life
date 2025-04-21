<?php 
declare(strict_types=1);

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\{
    TrackerLoginsController,
    FitbitAuthController,
    GarminAuthController
};

use App\Http\Controllers\Webhook\{
    TrackersController,
    TestTrackersController,
    HubspotsController,
    UserActivitiesController,
    WebhooksController,
    OrdersController
};

Route::group(['prefix' => 'tracker'], function(){
    Route::get('oauth/strava', [TrackerLoginsController::class,'redirectToAuthUrl'])->name('strava.oauth');
    Route::get('login',[TrackerLoginsController::class, 'index'])->name('tracker.login');
    Route::get('logout',[TrackerLoginsController::class, 'index'])->name('tracker.logut');
    Route::get('user/activities',[TrackerLoginsController::class, 'userActivities'])->name('tracker.user.activities');
    Route::get('strava/callback',[TrackerLoginsController::class, 'stravaCallback']);
    
    Route::get('/fitbit/auth/{state?}', [FitbitAuthController::class, 'redirectToFitbit'])->name('fitbit.auth');
    Route::get('/oauth/fitbit/{state?}', [FitbitAuthController::class, 'redirectToFitbit'])->name('fitbit.oauth');
    Route::get('/fitbit/callback', [FitbitAuthController::class, 'handleCallback'])->name('fitbit.callback');
    Route::get('/fitbit/refresh', [FitbitAuthController::class, 'refreshToken'])->name('fitbit.refresh');
    Route::get('/oauth/garmin/{state?}', [GarminAuthController::class, 'redirectToGarmin'])->name('garmin.oauth');
    Route::get('garmin/callback',[GarminAuthController::class, 'handleCallback'])->name('garmin.callback');
});

Route::group(['prefix' => 'shopify'], function(){
    Route::post('/webhooks/orders', [WebhooksController::class, 'handleOrderCreation']);
    Route::get('/cron/orders', [OrdersController::class, 'getOrders']);
});

Route::group(['prefix' => 'webhook'], function(){
   Route::post('v1/tracker/fitbit', [TrackersController::class,'fitbitTracker']); 
   Route::get('v1/tracker/fitbit', [TrackersController::class,'fitbitVerify']); 
   Route::post('hubspot/user/verification', [HubspotsController::class,'verifyUserEmail']); 
   
   Route::get('user/activity/distances/tracker', [UserActivitiesController::class,'userDistanceTracker']);
   
   Route::get('event/trigger-celebration-mail', [UserActivitiesController::class,'triggerCelebrationMail']);
   
   #Route::get('tracker/fitbit/user/distances', [TrackersController::class,'fitBitUserDistanceTracker']); 
   Route::get('tracker/fitbit/user/manual/distances', [TrackersController::class,'fitBiUserManualDistanceTracker']); 
   
    Route::get('v1/tracker/fitbit/test', [TrackersController::class,'testfitbit']); 
    
    Route::get('/user/hubspot-contact/verify',[OrdersController::class,'userHubspotVerification']);
    
    
    Route::post('test/v1/tracker/fitbit', [TestTrackersController::class,'fitbitTracker']); 
    Route::get('test/v1/tracker/fitbit', [TestTrackersController::class,'fitbitVerify']);
    #Route::get('test/tracker/fitbit/user/distances', [TestTrackersController::class,'fitBitUserDistanceTracker']); 
    Route::get('test/tracker/fitbit/user/manual/distances', [TestTrackersController::class,'fitBiUserManualDistanceTracker']); 
    Route::get('test/v1/tracker/fitbit/test', [TestTrackersController::class,'testfitbit']); 
  
});

Route::get('/shopify/orders', [OrdersController::class,'orderList']);
Route::post('/shopify/orders', [OrdersController::class,'orderList']);
