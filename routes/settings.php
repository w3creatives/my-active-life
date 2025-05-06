<?php

declare(strict_types=1);

use App\Http\Controllers\DeviceSyncController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/device-sync', [DeviceSyncController::class, 'index'])->name('profile.device-sync.edit');
    Route::get('settings/device-sync/{sourceSlug}', [DeviceSyncController::class, 'connect'])->name('profile.device-sync.connect');
    Route::post('settings/device-sync/disconnect/{source}', [DeviceSyncController::class, 'disconnect'])->name('profile.device-sync.disconnect');
    Route::get('settings/device-sync/callback/{sourceSlug}', [DeviceSyncController::class, 'trackerCallback'])->name('profile.device-sync.callback');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('settings/password', [PasswordController::class, 'update'])->name('password.update');

    Route::get('settings/appearance', function () {
        return Inertia::render('settings/appearance');
    })->name('appearance');
});
