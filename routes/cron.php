<?php

declare(strict_types=1);

use App\Http\Controllers\Cron\DatasourcePointTrackersController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'cron'], function () {
    Route::get('datasource/points/tracker', [DatasourcePointTrackersController::class, 'tracker']);
});
