<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\{
    DashboardController,
    UsersController,
    EventsController
};

Route::group(['prefix' => 'admin','namespace' => 'Admin','middleware' => 'admin'],function () {
    Route::get('/show', [DashboardController::class,'show'])->name('admin.show');

    Route::get('/users', [UsersController::class,'index'])->name('admin.users');

    Route::get('/events', [EventsController::class,'index'])->name('admin.events');
    Route::get('/events/{id}/edit', [EventsController::class,'create'])->name('admin.events.edit');
    Route::get('/events/add', [EventsController::class,'create'])->name('admin.events.add');

    Route::post('/events/{id}/edit', [EventsController::class,'store']);
    Route::post('/events/add', [EventsController::class,'store']);
});
