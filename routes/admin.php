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
});
