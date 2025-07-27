<?php

declare(strict_types=1);

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

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
Route::get('test-user-token', function (\Illuminate\Http\Request $request) {

    $user = \App\Models\User::first();

    $user->logSourceConnected(['data_source_id' => 2]);
    $user->logSourceDisconnected(['data_source_id' => 2]);

    //return ['token' => $user->createToken('MyApp')->accessToken];
});
Route::get('test-email', function () {


    $accessToken = "eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiIyMkNLRzIiLCJzdWIiOiJDOU5KWUwiLCJpc3MiOiJGaXRiaXQiLCJ0eXAiOiJhY2Nlc3NfdG9rZW4iLCJzY29wZXMiOiJyYWN0IHJzZXQgcndlaSBycHJvIHJudXQgcnNsZSIsImV4cCI6MTc1MjI3NzEwMSwiaWF0IjoxNzUyMjQ4MzAxfQ.kdO3BvLuOCT75PzMAGTUblwZJhNYu4Um5faX3Xyghj8";

    $httpClient = new \GuzzleHttp\Client([
        'base_uri' => 'https://api.fitbit.com/1/',
        'headers' => [
            'Authorization' => sprintf('Bearer %s', $accessToken),
            'Accept' => 'application/json',
        ],
    ]);

    $date = "2025-03-09";

    $response = $httpClient->get("user/-/activities/date/{$date}.json");

    $result = json_decode($response->getBody()->getContents(), true);

    $data = $result['activities'];
    $distances = $result['summary']['distances'];

    dd($data,$distances);

    $event = App\Models\Event::find(89);
    $user = App\Models\User::find(165220);
    $test = (new \App\Services\EventService(new \App\Repositories\EventRepository, new \App\Repositories\UserPointRepository))->userStreaks($user, $event);
dd($test);
    // $userStreak



    dd($user->userStreaks()->where('event_id', $event->id)->first(), $event->streaks()->first());
    (new App\Services\EventService(
        new App\Repositories\EventRepository(), new App\Repositories\UserPointRepository()
    ))->checkUserCelebrations($user, $event);
});
Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth'])->group(function () {
    // Dashboard routes
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/stats', [DashboardController::class, 'stats'])->name('stats');
    Route::get('/tutorials', [DashboardController::class, 'tutorials'])->name('tutorials');
    Route::get('/follow', [DashboardController::class, 'follow'])->name('follow');

    Route::post('/unfollow/{type}', [DashboardController::class, 'unfollow'])->name('unfollow');
    Route::post('/follow/{type}', [DashboardController::class, 'follow_request'])->name('follow.request');

    // User points routes
    Route::get('/user-points', [DashboardController::class, 'getUserPoints'])->name('user.points');
    Route::get('/user-daily-points', [DashboardController::class, 'getUserDailyPoints'])->name('user.daily.points');
    Route::get('/user-stats', [DashboardController::class, 'getUserStats'])->name('user.stats');
    Route::post('/add-points', [DashboardController::class, 'addPoints'])->name('user.add-points');

    Route::get('/conversations', [DashboardController::class, 'conversations'])->name('user.conversations');
    Route::get('/conversations/new', [DashboardController::class, 'newConversation'])->name('user.conversations.new');

    // Progressive data loading API routes
    Route::get('/api/tutorials-data', [DashboardController::class, 'getTutorialsData'])->name('api.tutorials.data');

    // Granular follow page API routes
    Route::get('/api/follow/user-followings', [DashboardController::class, 'getUserFollowings'])->name('api.follow.user-followings');
    Route::get('/api/follow/team-followings', [DashboardController::class, 'getTeamFollowings'])->name('api.follow.team-followings');
    Route::get('/api/follow/available-users', [DashboardController::class, 'getAvailableUsers'])->name('api.follow.available-users');
    Route::get('/api/follow/available-teams', [DashboardController::class, 'getAvailableTeams'])->name('api.follow.available-teams');

    // Granular stats page API routes
    Route::get('/api/stats/basic', [DashboardController::class, 'getBasicStats'])->name('api.stats.basic');
    Route::get('/api/stats/achievements', [DashboardController::class, 'getAchievements'])->name('api.stats.achievements');

    // Event selection route (temporary session-based)
    Route::post('/events/select-temp', [DashboardController::class, 'selectTempEvent'])->name('events.select-temp');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/webhook.php';
require __DIR__.'/cron.php';
require __DIR__.'/admin.php';
