<?php

declare(strict_types=1);

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserStatsController;
use App\Http\Controllers\TeamsController;
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
Route::get('test-user-token', function (Illuminate\Http\Request $request) {

    $d = ['daily_steps', 'run', 'walk'];

    $d[] = 'other';

    dd($d);

    $participation = App\Models\EventParticipation::find(57965);

    dd($participation, $participation->isModalityOverridden('other'));
    $settings = $participation->settings;

    $participationSetting = json_decode($settings, true);

    $modalityOverrides = $participationSetting['modality_overrides'] ?? ['daily_steps', 'run', 'walk'];

    dd(in_array('run', $modalityOverrides));

    $data = ['userAccessToken' => 3, 'callbackURLf' => 6665];

    dd(! isset($data['userAccessToken']) || ! isset($data['callbackURL']));
    $data = [['name' => 'test']];
    //  \Illuminate\Support\Facades\Storage::disk('public')->put(sprintf('webhooks/%s.json', \Illuminate\Support\Str::uuid()->toString()), json_encode($data));

    $data = json_decode(file_get_contents(public_path('garmin.json')), true);
    $activities = collect($data);

    $activities = $activities->map(function ($activity) {
        if (isset($activity['distanceInMeters'])) {
            $date = Carbon\Carbon::createFromTimestamp($activity['startTimeInSeconds'])->format('Y-m-d');
            $distance = round(($activity['distanceInMeters'] / 1609.344), 3);

            $modality = match ($activity['activityType']) {
                'RUNNING', 'TRACK_RUNNING', 'STREET_RUNNING', 'TREADMILL_RUNNING', 'TRAIL_RUNNING', 'VIRTUAL_RUN', 'INDOOR_RUNNING', 'OBSTACLE_RUN', 'OBSTACLE_RUNNING', 'ULTRA_RUN', 'ULTRA_RUNNING' => 'run',
                'WALKING', 'CASUAL_WALKING', 'SPEED_WALKING', 'GENERIC' => 'walk',
                'CYCLING', 'CYCLOCROSS', 'DOWNHILL_BIKING', 'INDOOR_CYCLING', 'MOUNTAIN_BIKING', 'RECUMBENT_CYCLING', 'ROAD_BIKING', 'TRACK_CYCLING', 'VIRTUAL_RIDE' => 'bike',
                'SWIMMING', 'LAP_SWIMMING', 'OPEN_WATER_SWIMMING' => 'swim',
                'WALKING', 'CASUAL_WALKING', 'SPEED_WALKING', 'GENERIC' => 'daily_steps',
                'HIKING', 'CROSS_COUNTRY_SKIING', 'MOUNTAINEERING', 'ELLIPTICAL', 'STAIR_CLIMBING' => 'other',
                default => 'daily_steps',
            };
            $time = $activity['startTimeInSeconds'];

            return compact('date', 'distance', 'modality');
        }
    })->toArray();

    dd($activities);
    $date = Carbon\Carbon::now();
    $nextDate = $date->copy()->addDay();

    $currentStart = Carbon\Carbon::parse('2025-08-01');
    $currentCronEnd = Carbon\Carbon::now();

    while ($currentStart->lte($currentCronEnd)) {

        $currentEnd = $currentStart->copy()->addDays(2);
        echo $currentStart,' = ',$currentEnd,"\n\n | ";
        $currentStart = $currentEnd->copy();

        echo $currentStart,' = ',$currentEnd,"\n\n </br>";
    }

    // dd($date, $nextDate, $date);
    $a = null;
    $b = 2;

    $item = $c ?? $a ?? $b;

    dd($item);

    $user = App\Models\User::first();

    $user->logSourceConnected(['data_source_id' => 2]);
    $user->logSourceDisconnected(['data_source_id' => 2]);

    // return ['token' => $user->createToken('MyApp')->accessToken];
});
Route::get('test-email', function () {

    $accessToken = 'eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiIyMkNLRzIiLCJzdWIiOiJDOU5KWUwiLCJpc3MiOiJGaXRiaXQiLCJ0eXAiOiJhY2Nlc3NfdG9rZW4iLCJzY29wZXMiOiJyYWN0IHJzZXQgcndlaSBycHJvIHJudXQgcnNsZSIsImV4cCI6MTc1MjI3NzEwMSwiaWF0IjoxNzUyMjQ4MzAxfQ.kdO3BvLuOCT75PzMAGTUblwZJhNYu4Um5faX3Xyghj8';

    $httpClient = new GuzzleHttp\Client([
        'base_uri' => 'https://api.fitbit.com/1/',
        'headers' => [
            'Authorization' => sprintf('Bearer %s', $accessToken),
            'Accept' => 'application/json',
        ],
    ]);

    $date = '2025-03-09';

    $response = $httpClient->get("user/-/activities/date/{$date}.json");

    $result = json_decode($response->getBody()->getContents(), true);

    $data = $result['activities'];
    $distances = $result['summary']['distances'];

    dd($data, $distances);

    $event = App\Models\Event::find(89);
    $user = App\Models\User::find(165220);
    $test = (new App\Services\EventService(new App\Repositories\EventRepository, new App\Repositories\UserPointRepository))->userStreaks($user, $event);
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

    Route::get('/trophy-case', [DashboardController::class, 'trophyCase'])->name('trophy-case');
    //Route::get('/teams', [DashboardController::class, 'teams'])->name('teams');

    // User preferred event route
    Route::post('/user/set-preferred-event', [DashboardController::class, 'setPreferredEvent'])->name('user.set-preferred-event');
    Route::get('/user/stats/{type}', [UserStatsController::class, 'getUserStats'])->name('userstats');
    Route::get('/user/event/{type}', [DashboardController::class, 'getUserEventDetails'])->name('user.event.details');

    // Teams route
    Route::get('/teams', [TeamsController::class, 'index'])->name('teams');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/webhook.php';
require __DIR__.'/cron.php';
require __DIR__.'/admin.php';
