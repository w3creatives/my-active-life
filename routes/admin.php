<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\{
    ActivitiesController,
    DashboardController,
    MilestonesController,
    UsersController,
    EventsController,
    ImpersonateController,
    StreaksController,
    EmailBuildersController,
};

Route::impersonate();

Route::group(['prefix' => 'impersonated', 'namespace' => 'Admin', 'middleware' => 'auth'], function () {
    Route::get('login', [ImpersonateController::class, 'login'])->name('impersonate.login');
});

Route::group(['prefix' => 'admin', 'namespace' => 'Admin', 'middleware' => 'admin'], function () {

    Route::get('/show', [DashboardController::class, 'show'])->name('admin.show');
    /**
     * Users Routes
     */
    Route::get('/users', [UsersController::class, 'index'])->name('admin.users');
    Route::get('/users/create', [UsersController::class, 'create'])->name('admin.users.create');
    Route::post('/users/create', [UsersController::class, 'store']);
    Route::get('/users/{id}/edit', [UsersController::class, 'create'])->name('admin.users.edit');
    Route::post('/users/{id}/edit', [UsersController::class, 'store']);


    /**
     * Events Routes
     */
    Route::get('/events', [EventsController::class, 'index'])->name('admin.events');
    Route::get('/events/{id}/edit', [EventsController::class, 'create'])->name('admin.events.edit');
    Route::get('/events/create', [EventsController::class, 'create'])->name('admin.events.add');
    Route::post('/events/{id}/edit', [EventsController::class, 'store']);
    Route::post('/events/create', [EventsController::class, 'store']);

    /**
     * Regular Event Milestones routes
     */
    Route::get('/events/{id}/milestones', [MilestonesController::class, 'index'])->name('admin.events.milestones');
    Route::get('/events/{id}/milestones/create', [MilestonesController::class, 'create'])->name('admin.events.milestones.create');
    Route::post('/events/{id}/milestones/create', [MilestonesController::class, 'store']);
    Route::get('/events/{id}/milestones/{milestoneId}/edit', [MilestonesController::class, 'create'])->name('admin.events.milestones.edit');
    Route::post('/events/{id}/milestones/{milestoneId}/edit', [MilestonesController::class, 'store']);
    Route::get('/events/{id}/milestones/{milestoneId}/view', [MilestonesController::class, 'view'])->name('admin.events.milestones.view');

    /**
     * Fit Life Event Activities routes
     */
    Route::get('/events/{id}/activities', [ActivitiesController::class, 'index'])->name('admin.events.activities');
    Route::get('/events/{id}/activities/create/{activityId?}', [ActivitiesController::class, 'create'])->name('admin.events.activities.create');
    Route::post('/events/{id}/activities/create/{activityId?}', [ActivitiesController::class, 'store']);
    Route::delete('/events/{id}/activities/delete/{activityId}', [ActivitiesController::class, 'destroy'])->name('admin.events.activities.delete');

    Route::get('/events/{id}/activities/{activityId}/milestones', [MilestonesController::class, 'index'])->name('admin.events.activity.milestones');
    Route::get('/events/{id}/activities/{activityId}/milestones/create', [MilestonesController::class, 'create'])->name('admin.events.activity.milestones.create');
    Route::post('/events/{id}/activities/{activityId}/milestones/create', [MilestonesController::class, 'store']);

    Route::get('/events/{id}/activities/{activityId}/milestones/{milestoneId}/edit', [MilestonesController::class, 'create'])->name('admin.events.activity.milestones.edit');
    Route::post('/events/{id}/activities/{activityId}/milestones/{milestoneId}/edit', [MilestonesController::class, 'store']);

    /**
     * Promotional Events Streaks routes
     */
    Route::get('/events/{id}/streaks', [StreaksController::class, 'index'])->name('admin.events.streaks');
    Route::get('/events/{id}/streaks/create', [StreaksController::class, 'create'])->name('admin.events.streaks.create');
    Route::post('/events/{id}/streaks/create', [StreaksController::class, 'store']);

    Route::get('/events/{id}/streaks/{streakId}/edit', [StreaksController::class, 'create'])->name('admin.events.streaks.edit');
    Route::post('/events/{id}/streaks/{streakId}/edit', [StreaksController::class, 'store']);
    Route::delete('/events/{id}/streaks/{streakId}/delete', [StreaksController::class, 'destroy'])->name('admin.events.streaks.delete');

    /**
     * Email Builder routes
     */
    Route::get('/email-builders/create', [EmailBuildersController::class, 'create'])->name('admin.email.builders.create');
    Route::post('/email-builders/create', [EmailBuildersController::class, 'store']);
    Route::get('/email-builders/{id}/edit', [EmailBuildersController::class, 'create'])->name('admin.email.builders.edit');
    Route::post('/email-builders/{id}/edit', [EmailBuildersController::class, 'store']);
    Route::get('/email-builders', [EmailBuildersController::class, 'index'])->name('admin.email.builders');

    Route::delete('/email-builders/{id}/delete', [EmailBuildersController::class, 'destroy'])->name('admin.email.builders.destroy');
});
