<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cron;

use App\Http\Controllers\Controller;
use App\Models\DataSource;
use App\Models\DatasourcePointTracker;
use App\Models\UserPoint;
use Carbon\Carbon;

final class DatasourcePointTrackersController extends Controller
{
    public function tracker()
    {
        $date = Carbon::now()->subDays(0)->format('Y-m-d');

        $dataSources = DataSource::query()->exceptManual()->get();

        foreach ($dataSources as $dataSource) {
            $datasourcePoints = UserPoint::query()
                ->selectRaw('SUM(amount) as total_point, date, data_source_id')
                ->where(['data_source_id' => $dataSource->id, 'date' => $date])
                ->groupBy(['date', 'data_source_id'])->get()
                ->sum('total_point');

            DatasourcePointTracker::create(['data_source_id' => $dataSource->id, 'total_point' => $datasourcePoints, 'date' => $date]);
        }
    }
}
