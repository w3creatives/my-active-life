<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\ActivitiesController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EmailBuildersController;
use App\Http\Controllers\Admin\EventsController;
use App\fddHttp\Controllers\Admin\ImpersonateController;
use App\Http\Controllers\Admin\MilestonesController;
use App\Http\Controllers\Admin\ReportsController;
use App\Http\Controllers\Admin\StreaksController;
use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\Admin\EventTutorialsController;
use Illuminate\Support\Facades\Route;

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

    Route::get('/users/merge-accounts', [UsersController::class, 'mergeAccounts'])->name('admin.users.merge-accounts');
    Route::post('/users/merge-accounts', [UsersController::class, 'mergeAccounts']);

    /**
     `  * Events Routes
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
     * Event Tutorials
     */
    Route::get('/events/{eventId}/tutorials', [EventTutorialsController::class, 'create'])->name('admin.events.tutorials');
    Route::post('/events/{eventId}/tutorials', [EventTutorialsController::class, 'store']);
    Route::delete('/events/{eventId}/tutorials/{id}', [EventTutorialsController::class, 'destroy'])->name('admin.events.tutorials.delete');
    /**
     * Email Builder routes
     */
    Route::get('/email-builders/create', [EmailBuildersController::class, 'create'])->name('admin.email.builders.create');
    Route::post('/email-builders/create', [EmailBuildersController::class, 'store']);
    Route::get('/email-builders/{id}/edit', [EmailBuildersController::class, 'create'])->name('admin.email.builders.edit');
    Route::post('/email-builders/{id}/edit', [EmailBuildersController::class, 'store']);
    Route::get('/email-builders', [EmailBuildersController::class, 'index'])->name('admin.email.builders');
    Route::delete('/email-builders/{id}/delete', [EmailBuildersController::class, 'destroy'])->name('admin.email.builders.destroy');

    /**
     * Reports routes
     */
    Route::get('reports/users', [ReportsController::class, 'users'])->name('admin.reports.users');
    Route::get('reports/users/events/{type}', [ReportsController::class, 'events'])->name('admin.reports.events');
    Route::get('reports/users/datasources', [ReportsController::class, 'dataSources'])->name('admin.reports.datasources');

    Route::get('reports/source/point-tracker', [ReportsController::class, 'pointTracker'])->name('admin.reports.point-tracker');
});
