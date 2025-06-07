<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\{
    ActivitiesController,
    DashboardController,
    MilestonesController,
    UsersController,
    EventsController};

Route::group(['prefix' => 'admin', 'namespace' => 'Admin', 'middleware' => 'admin'], function () {
    Route::get('/show', [DashboardController::class, 'show'])->name('admin.show');

    Route::get('/users', [UsersController::class, 'index'])->name('admin.users');

    /**
     * Events Routes
     */
    Route::get('/events', [EventsController::class, 'index'])->name('admin.events');
    Route::get('/events/{id}/edit', [EventsController::class, 'create'])->name('admin.events.edit');
    Route::get('/events/add', [EventsController::class, 'create'])->name('admin.events.add');
    Route::post('/events/{id}/edit', [EventsController::class, 'store']);
    Route::post('/events/add', [EventsController::class, 'store']);

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

});
