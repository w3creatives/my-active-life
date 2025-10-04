<?php

declare(strict_types=1);

use App\Http\Controllers\DeviceSyncController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\RtyGoalsController;
use App\Http\Controllers\Webhook\TrackerWebhooksController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
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

    Route::get('settings/manual-entry', function () {
        return Inertia::render('settings/manual-entry');
    })->name('manual-entry');

    Route::get('settings/privacy', function () {
        return Inertia::render('settings/privacy');
    })->name('privacy');

    Route::get('settings/import-previous-years', function () {
        return Inertia::render('settings/import-previous-years');
    })->name('profile.import-previous-years');

    Route::get('settings/rty-goals', function () {
        $user = auth()->user();

        // Get the user's preferred event or the first active event
        $preferredEvent = null;
        if ($user->preferred_event_id) {
            $preferredEvent = App\Models\Event::find($user->preferred_event_id);
        }

        // If no preferred event, get the first active event the user is participating in
        if (! $preferredEvent) {
            $participation = $user->participations()
                ->whereHas('event', function ($query) {
                    $query->where('end_date', '>=', now());
                })
                ->with('event')
                ->first();

            if ($participation) {
                $preferredEvent = $participation->event;
            }
        }

        return Inertia::render('settings/rty-goals', [
            'preferredEvent' => $preferredEvent,
            'eventId' => $preferredEvent?->id,
        ]);
    })->name('profile.rty-goals');

    // RTY Goals API routes
    Route::get('settings/rty-goals/goal', [RtyGoalsController::class, 'getGoal'])->name('profile.rty-goals.get-goal');
    Route::get('settings/rty-goals/modalities', [RtyGoalsController::class, 'getModalities'])->name('profile.rty-goals.get-modalities');
    Route::post('settings/rty-goals/goal', [RtyGoalsController::class, 'updateGoal'])->name('profile.rty-goals.update-goal');
    Route::post('settings/rty-goals/modality', [RtyGoalsController::class, 'updateModality'])->name('profile.rty-goals.update-modality');

    Route::get('settings/tracker-attitude', function () {
        return Inertia::render('settings/tracker-attitude');
    })->name('profile.tracker-attitude');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('settings/password', [PasswordController::class, 'update'])->name('password.update');

    Route::get('settings/appearance', function () {
        return Inertia::render('settings/appearance');
    })->name('appearance');
});

Route::get('settings/device-sync/webhook/{sourceSlug}/verify', [TrackerWebhooksController::class, 'verifyWebhook'])->name('profile.device-sync.webhook.verify');
Route::any('settings/device-sync/webhook/{sourceSlug}', [TrackerWebhooksController::class, 'webhookAction'])
    ->name('profile.device-sync.webhook')
    ->withoutMiddleware([VerifyCsrfToken::class]);
