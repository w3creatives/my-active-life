<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\{
    DashboardController,
    UsersController
};

Route::group(['prefix' => 'admin','namespace' => 'Admin','middleware' => 'admin'],function () {
    Route::get('/dashboard', [DashboardController::class,'index'])->name('admin.dashboard');
    Route::get('/show', [DashboardController::class,'show'])->name('admin.show');

    Route::get('/users', [UsersController::class,'index'])->name('admin.users');
});
