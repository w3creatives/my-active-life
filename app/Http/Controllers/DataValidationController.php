<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\UserPoint;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DataValidationController extends Controller
{
    public function findInvalidDataSourcePoints(Request $request)
    {
        $request->validate([
            'event_id' => [
                'nullable',
                Rule::exists(Event::class, 'id'),
            ],
            'user_id' => 'nullable|integer',
            'data_source_id' => 'nullable|integer',
            'limit' => 'nullable|integer|min:1|max:1000',
        ]);

        $query = UserPoint::query()
            ->select(['user_points.*', 'users.email', 'data_sources.name as data_source_name'])
            ->join('users', 'user_points.user_id', '=', 'users.id')
            ->join('data_sources', 'user_points.data_source_id', '=', 'data_sources.id')
            ->whereNotExists(function ($subQuery) {
                $subQuery->select(DB::raw(1))
                    ->from('data_source_profiles')
                    ->whereColumn('data_source_profiles.user_id', 'user_points.user_id')
                    ->whereColumn('data_source_profiles.data_source_id', 'user_points.data_source_id');
            });

        if ($request->event_id) {
            $query->where('user_points.event_id', $request->event_id);
        }

        if ($request->user_id) {
            $query->where('user_points.user_id', $request->user_id);
        }

        if ($request->data_source_id) {
            $query->where('user_points.data_source_id', $request->data_source_id);
        }

        $limit = $request->limit ?? 100;
        $invalidPoints = $query->limit($limit)->get();

        // Group by user for summary
        $userSummary = $invalidPoints->groupBy('user_id')->map(function ($points, $userId) {
            $user = $points->first();
            return [
                'user_id' => $userId,
                'email' => $user->email,
                'invalid_data_sources' => $points->groupBy('data_source_id')->map(function ($dsPoints, $dataSourceId) {
                    $ds = $dsPoints->first();
                    return [
                        'data_source_id' => $dataSourceId,
                        'data_source_name' => $ds->data_source_name,
                        'points_count' => $dsPoints->count(),
                        'total_amount' => $dsPoints->sum('amount'),
                        'date_range' => [
                            'earliest' => $dsPoints->min('date'),
                            'latest' => $dsPoints->max('date'),
                        ],
                    ];
                })->values(),
            ];
        })->values();

        $response = [
            'summary' => [
                'total_affected_users' => $userSummary->count(),
                'total_invalid_points' => $invalidPoints->count(),
                'total_amount' => $invalidPoints->sum('amount'),
            ],
            'affected_users' => $userSummary,
            'sample_points' => $invalidPoints->take(20)->map(function ($point) {
                return [
                    'id' => $point->id,
                    'user_id' => $point->user_id,
                    'email' => $point->email,
                    'data_source_id' => $point->data_source_id,
                    'data_source_name' => $point->data_source_name,
                    'amount' => $point->amount,
                    'date' => $point->date,
                    'event_id' => $point->event_id,
                    'modality' => $point->modality,
                ];
            }),
        ];

        return response()->json($response);
    }
}