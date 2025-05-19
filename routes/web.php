<?php

declare(strict_types=1);

use App\Http\Controllers\DashboardController;
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

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth'])->group(function () {
    // Dashboard routes
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/stats', [DashboardController::class, 'stats'])->name('stats');

    // User points routes
    Route::get('/user-points', [DashboardController::class, 'getUserPoints'])->name('user.points');
    Route::get('/user-stats', [DashboardController::class, 'getUserStats'])->name('user.stats');
    Route::post('/add-points', [DashboardController::class, 'addPoints'])->name('user.add-points');

    // Event selection route (temporary session-based)
    Route::post('/events/select-temp', [DashboardController::class, 'selectTempEvent'])->name('events.select-temp');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/webhook.php';
require __DIR__.'/admin.php';
