<?php

declare(strict_types=1);

use App\Http\Controllers\API\AchievementsController;
use App\Http\Controllers\API\EventsController;
use App\Http\Controllers\API\EventTutorialsController;
use App\Http\Controllers\API\PasswordsController;
use App\Http\Controllers\API\ProfilesController;
use App\Http\Controllers\API\QuestsController;
use App\Http\Controllers\API\SessionsController;
use App\Http\Controllers\API\TeamsController;
use App\Http\Controllers\API\UserFollowsController;
use App\Http\Controllers\API\UserNotesController;
use App\Http\Controllers\API\UserPointsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('login', [SessionsController::class, 'login']);
Route::post('forgot-password', [PasswordsController::class, 'forgotPassword']);
Route::post('reset-password', [PasswordsController::class, 'resetPassword']);

Route::get('/flag-banner', function () {
    return response()->json(['flag' => asset('static/flag/banner-1471038_1280.png')]);
});

Route::post('event/template/update', [EventsController::class, 'updateEventTemplate']);
Route::get('test/user/points/listing', [UserPointsController::class, 'testlistView']);
Route::get('unit-test/user/points/listing', [UserPointsController::class, 'unittestListView']);
Route::middleware('auth:api')->group(function () {

    Route::post('user/update-password', [PasswordsController::class, 'updatePassword']);

    Route::get('user/points/listing', [UserPointsController::class, 'listView']);
    Route::get('user/points/view', [UserPointsController::class, 'viewPoint']);
    Route::get('user/points/list', [UserPointsController::class, 'list']);

    Route::get('user/points', [UserPointsController::class, 'index']);
    Route::post('user/points', [UserPointsController::class, 'store']);
    Route::patch('user/points', [UserPointsController::class, 'updatePoints']);
    Route::patch('user/points/{id}', [UserPointsController::class, 'store']);
    Route::post('user/event/sync-points', [UserPointsController::class, 'syncPoints']);
    Route::get('user/stats', [UserPointsController::class, 'profileStats']);
    Route::get('user/points/total', [UserPointsController::class, 'totalPoints']);

    Route::get('user/points/last-30-days', [UserPointsController::class, 'last30DaysStats']);
    Route::get('user/points/by-modality', [UserPointsController::class, 'modalityTotalsByEvent']);

    Route::get('achievements', [AchievementsController::class, 'index']);

    Route::get('user/note', [UserNotesController::class, 'index']);
    Route::patch('user/note', [UserNotesController::class, 'store']);
    Route::get('user/events/participants', [ProfilesController::class, 'eventParticipants']);
    Route::post('user/event/privacy', [ProfilesController::class, 'updateEventParticipantPrivacy']);
    Route::post('user/event/modality', [ProfilesController::class, 'updateEventModality']);
    Route::get('teams', [TeamsController::class, 'all']);
    Route::get('teams/team/{id}', [TeamsController::class, 'findOne']);
    Route::get('teams/achievements', [TeamsController::class, 'achievements']);
    Route::post('teams/invite/membership', [TeamsController::class, 'inviteMembership']);
    Route::post('teams/create', [TeamsController::class, 'store']);
    Route::patch('teams/update/{id}', [TeamsController::class, 'store']);
    Route::post('/teams/dissolve', [TeamsController::class, 'dissolveTeam']);

    Route::post('/teams/transfer-admin-role', [TeamsController::class, 'transferTeamAdminRole']);

    Route::post('teams/join-request', [TeamsController::class, 'joinTeam']);
    Route::post('teams/cancel-request', [TeamsController::class, 'cancelJoinTeam']);
    Route::post('teams/leave', [TeamsController::class, 'leaveTeam']);
    Route::post('teams/member/remove', [TeamsController::class, 'removeMember']);
    Route::post('teams/membership-request/{type}', [TeamsController::class, 'joinTeamRequest']);
    Route::get('teams/membership/requests', [TeamsController::class, 'membershipRequests']);
    Route::get('teams/membership/invites', [TeamsController::class, 'membershipInvites']);

    Route::post('teams/follow/request/{type}', [TeamsController::class, 'teamFollowRequestAction']);
    Route::get('teams/followers', [TeamsController::class, 'teamFollowers']);
    Route::get('teams/follow-to/list', [TeamsController::class, 'teamToFollowList']);

    Route::get('teams/invitation/users/search', [TeamsController::class, 'searchInvitationUsers']);
    Route::get('teams/points/total', [TeamsController::class, 'totalPoints']);
    Route::get('teams/points/monthlies', [TeamsController::class, 'monthliesPoints']);

    Route::get('events', [EventsController::class, 'all']);
    Route::get('events/event/{id}', [EventsController::class, 'findOne']);
    Route::get('event/missing-miles/years', [EventsController::class, 'eventMissingYears']);
    Route::get('event/amerithon-distances', [EventsController::class, 'getAmerithonPathDistances']);
    Route::get('modalities', [EventsController::class, 'getModalities']);
    Route::get('event/modalities', [EventsController::class, 'getEventModalities']);
    Route::post('event/miles/import', [EventsController::class, 'importEventMiles']);
    Route::get('event/goals', [EventsController::class, 'goals']);

    Route::get('user/profile/basic', [ProfilesController::class, 'show']);
    Route::get('user/profile/complete', [ProfilesController::class, 'all']);
    Route::patch('user/profile', [ProfilesController::class, 'store']);
    Route::post('user/follow/team/request', [ProfilesController::class, 'requestTeamFollow']);
    Route::post('user/follow/team/request/{type}', [ProfilesController::class, 'requestTeamFollow']);
    Route::get('user/teams/following', [ProfilesController::class, 'teamFollowing']);
    Route::get('user/teams/following/requests', [ProfilesController::class, 'teamFollowRequests']);

    Route::get('user/team/membership/invites', [UserPointsController::class, 'membershipInvites']);
    Route::post('user/team/membership-request/{type}', [UserPointsController::class, 'membershipInviteAction']);

    // User team invitations
    Route::get('user/team/invitations', [UserPointsController::class, 'getUserTeamInvitations'])->name('api.user.team.invitations');
    Route::post('user/team/invitation/accept', [UserPointsController::class, 'acceptTeamInvitation'])->name('api.user.team.invitation.accept');
    Route::post('user/team/invitation/decline', [UserPointsController::class, 'declineTeamInvitation'])->name('api.user.team.invitation.decline');

    Route::get('source/profiles', [ProfilesController::class, 'sourceProfile']);
    Route::get('user/source/profiles', [ProfilesController::class, 'userSourceProfile']);
    Route::post('user/source/profiles/create', [ProfilesController::class, 'create']);
    Route::delete('user/source/profiles/delete', [ProfilesController::class, 'destroy']);

    Route::post('user/notifications/update', [ProfilesController::class, 'updateNotification']);
    Route::post('user/manual-entry/update', [ProfilesController::class, 'updateManualEntry']);
    Route::post('user/tracker-attitude/update', [ProfilesController::class, 'updateTrackerAt']);
    Route::post('user/rty-mileage-goal/update', [ProfilesController::class, 'rtyMileageGoal']);
    Route::get('user/setting', [ProfilesController::class, 'findSetting']);

    Route::get('quests/activities', [QuestsController::class, 'questActivities']);
    Route::post('quests/create', [QuestsController::class, 'registration']);
    Route::get('quests', [QuestsController::class, 'all']);
    Route::get('quest/{id}', [QuestsController::class, 'findOne']);
    Route::get('quests/{type}', [QuestsController::class, 'all']);
    Route::post('quests/update', [QuestsController::class, 'updateRegistration']);
    Route::delete('quests/delete', [QuestsController::class, 'deleteRegistration']);
    Route::post('quests/archive', [QuestsController::class, 'archiveRegistration']);
    Route::get('quests/journal', [QuestsController::class, 'questJournal']);

    Route::get('follow/user-participations', [UserFollowsController::class, 'participates']);
    Route::get('follow/followers', [UserFollowsController::class, 'followers']);
    Route::get('follow/followings', [UserFollowsController::class, 'followings']);
    Route::post('follow/undo-following', [UserFollowsController::class, 'undoFollowing']);
    Route::post('follow/follow-request/{type}', [UserFollowsController::class, 'followRequestAction']);
    Route::post('follow/following/request', [UserFollowsController::class, 'requestFollowing']);
    Route::get('follow/following/requests', [UserFollowsController::class, 'followingRequests']);

    Route::get('event/tutorials', [EventTutorialsController::class, 'index']);
    Route::post('event/tutorials', [EventTutorialsController::class, 'store']);
    Route::patch('event/tutorials', [EventTutorialsController::class, 'update']);
    Route::delete('event/tutorials', [EventTutorialsController::class, 'destroy']);
    Route::get('event/all-tutorials', [EventTutorialsController::class, 'getAllTutorials']);
});
