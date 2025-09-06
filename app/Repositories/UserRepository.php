<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Event;
use App\Models\PointMonthly;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

final class UserRepository
{
    public function find($id)
    {
        return User::find($id);
    }

    public function findByEmail($email)
    {
        return User::where('email', 'ILIKE', $email)->first();
    }

    public function basic($request)
    {

        $user = $request->user_id ? $this->find($request->user_id) : $request->user();

        return $user->only([
            'email', 'first_name', 'last_name',
            'display_name', 'birthday', 'bio',
            'time_zone', 'time_zone_name', 'street_address1', 'street_address2',
            'city', 'state', 'country', 'zipcode', 'gender', 'settings',
            'shirt_size', 'preferred_event_id',
        ]);
    }

    public function profile($request)
    {

        $user = $request->user_id ? $this->find($request->user_id) : $request->user();

        $profile = $user->only([
            'email', 'first_name', 'last_name',
            'display_name', 'birthday', 'bio',
            'time_zone', 'time_zone_name', 'street_address1', 'street_address2',
            'city', 'state', 'country', 'zipcode', 'gender', 'settings',
            'shirt_size', 'preferred_event_id',
        ]);
        $profile['name'] = $profile['display_name'];
        unset($profile['display_name']);

        $eventId = $request->event_id ?? $user->preferred_event_id;

        $year = $request->year ? $request->year : Carbon::now()->format('Y');

        $lifetimePoint = $request->lifetimePoint;

        $cacheName = "user_profile_{$user->id}_{$eventId}_{$year}_{$lifetimePoint}_{$request->start_date}_{$request->end_date}_{$request->modality}_{$request->event_id}";
        /*
        if(Cache::has($cacheName)){
           return Cache::get($cacheName);
        }*/

        $hasUserAchievement = $user->achievements()->hasEvent($eventId)->count();

        if (! $hasUserAchievement) {
            $achievements = [];
        } else {
            $achievements = $user->achievements()->select(['accomplishment', 'date', 'achievement'])->hasEvent($eventId)->latest('accomplishment')->get()->groupBy('achievement');
        }

        $profile['achievements'] = $achievements;

        $date = Carbon::createFromDate($year, 01, 01);

        $startOfYear = $date->copy()->startOfYear()->format('Y-m-d');
        $endOfYear = $date->copy()->endOfYear()->format('Y-m-d');

        $point = $user->monthlyPoints()->selectRaw('SUM(amount) as totalPoint')
            ->hasEvent($eventId)
            ->where(function ($query) use ($lifetimePoint, $startOfYear, $endOfYear) {

                if (! $lifetimePoint) {
                    $query->whereBetween('date', [$startOfYear, $endOfYear]);
                }

                return $query;
            })
            ->first()->totalpoint;

        if ($lifetimePoint) {
            $year = 'Lifetime';
        }

        $profile['yearlyPoints'] = compact('point', 'year');

        $profile['recentActivity'] = $user->points()->where('event_id', $eventId)->latest()->select(['id', 'amount', 'date', 'modality'])->first();
        // $profile['recentActivity'] = $user->points()->where('event_id', $eventId)->latest()->select(['id','amount','date','modality'])->first();

        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $modality = $request->modality;
        $eventId = $request->event_id;

        // $participations = $user->participations()->with('event')->get();
        $participations = $user->participations()->with('event')->whereHas('event', function ($query) {
            $query->where('mobile_event', true);
        })->get();

        $points = $user->points()->with('event')
            ->where(function ($query) use ($startDate, $endDate, $modality, $eventId) {

                if ($startDate && $endDate) {
                    $query->whereDate('date', '>=', $startDate)
                        ->whereDate('date', '<=', $endDate);
                }

                if ($modality) {
                    $query->where('modality', $modality);
                }

                if ($eventId) {
                    $query->where('event_id', $eventId);
                }

                return $query;
            })
            ->get();

        $profile['points'] = $points;
        $profile['participations'] = $participations;

        Cache::put($cacheName, $profile, now()->addHours(2));

        return $profile;

    }

    public function achievements($event, $dateRange, $user)
    {

        $eventId = $event->id;

        [$today, $startOfMonth, $endOfMonth, $startOfWeek, $endOfWeek] = $dateRange;

        $achievements = $user->achievements()->select(['accomplishment', 'date', 'achievement'])->hasEvent($eventId)->latest('accomplishment')->get()->groupBy('achievement');

        $dayPoint = $user->points()->where('event_id', $eventId)->where('date', $today)->sum('amount');
        $weekPoint = $user->points()->where('event_id', $eventId)->where('date', '>=', $startOfWeek)->where('date', '<=', $endOfWeek)->sum('amount');
        $monthPoint = $user->points()->where('event_id', $eventId)->where('date', '>=', $startOfMonth)->where('date', '<=', $endOfMonth)->sum('amount');

        $achievements = $user->achievements()->select(['accomplishment', 'date', 'achievement'])->hasEvent($eventId)->latest('accomplishment')->get();

        $yearwisePoints = $user->points()->selectRaw('SUM(amount) miles,extract(year from "date") as year')->where('event_id', $eventId)->groupBy('year')->orderBy('year', 'DESC')->get();

        $totalPoints = $user->points()->where('event_id', $eventId)->sum('amount');

        $achievementData = [
            'best_day' => [
                'achievement' => 'best_day',
                'accomplishment' => null,
                'date' => null,
            ],
            'best_week' => [
                'achievement' => 'best_week',
                'accomplishment' => null,
                'date' => null,
            ],
            'best_month' => [
                'achievement' => 'best_month',
                'accomplishment' => null,
                'date' => null,
            ],
        ];

        if ($achievements->count()) {
            foreach ($achievements as $achievement) {
                $achievementData[$achievement->achievement]['accomplishment'] = $achievement->accomplishment;
                $achievementData[$achievement->achievement]['date'] = $achievement->date;
            }
        }

        $achievementData['current_day'] = [
            'achievement' => 'day',
            'accomplishment' => $dayPoint,
            'date' => $today,
        ];

        $achievementData['current_week'] = [
            'achievement' => 'week',
            'accomplishment' => $weekPoint,
            'date' => $endOfWeek,
        ];

        $achievementData['current_month'] = [
            'achievement' => 'month',
            'accomplishment' => $monthPoint,
            'date' => $endOfMonth,
        ];

        return [$achievementData, $totalPoints, $yearwisePoints];
    }

    public function createShopifyUser($data)
    {
        return User::create($data);
    }

    public function getMonthlyPoints(int $eventId, User $user): Collection
    {
        $event = Event::where('id', $eventId)->first();
        $eventStartDate = Carbon::parse($event->start_date);
        $eventEndDate = Carbon::parse($event->end_date);

        // Filter for current year only
        $currentYear = Carbon::now()->year;
        $currentYearStart = Carbon::create($currentYear, 1, 1)->startOfDay();
        $currentYearEnd = Carbon::create($currentYear, 12, 31)->endOfDay();
        
        // Use the intersection of event dates and current year
        $startDate = $eventStartDate->max($currentYearStart);
        $endDate = $eventEndDate->min($currentYearEnd);

        return PointMonthly::select('amount', 'date')
            ->where('event_id', $eventId)
            ->where('user_id', $user->id)
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->whereYear('date', $currentYear) // Additional filter for current year
            ->get()
            ->map(function ($item) {
                $item->label = Carbon::parse($item->date)->format('M'); // Show only month name (e.g., Dec)

                return $item;
            });
    }

    public function getYearlyPoints(int $eventId, User $user): Collection
    {
        $event = Event::where('id', $eventId)->first();
        $eventStartDate = Carbon::parse($event->start_date);
        $eventEndDate = Carbon::parse($event->end_date);

        return PointMonthly::select('amount', 'date')
            ->where('event_id', $eventId)
            ->where('user_id', $user->id)
            ->whereDate('date', '>=', $eventStartDate)
            ->whereDate('date', '<=', $eventEndDate)
            ->get()
            ->map(function ($item) {
                $item->label = Carbon::parse($item->date)->format('M y'); // e.g., Dec 25

                return $item;
            });
    }
}
