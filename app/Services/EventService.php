<?php

namespace App\Services;

use App\Repositories\EventRepository;
use Carbon\CarbonImmutable;
use Carbon\Carbon;
use \Illuminate\Support\Str;
use App\Models\{
    Event,
    UserPoint,
    Team
};
use App\Repositories\UserPointRepository;
use App\Traits\UserEventParticipationTrait;

class EventService
{

    use UserEventParticipationTrait;

    public function __construct(
        protected EventRepository $eventRepository,
        protected UserPointRepository $userPointRepository
    ) {}

    public function importManual($event, $manualEntry, $user)
    {

        $year = $manualEntry['year'];

        $miles = $manualEntry['miles'];

        $monthMile = (float)($miles ? ($miles / 12) : 0);

        $date = CarbonImmutable::createFromFormat('Y', $year);

        $startOfYear = $date->startOfYear();

        $user->totalPoints()->where('event_id', $event->id)->delete();

        $user->totalPoints()->create(['event_id' => $event->id, 'amount' => $miles]);

        foreach (range(0, 11) as $monthCount) {
            $yearMonth = $startOfYear->addMonths($monthCount);

            $monthLastDay = $yearMonth->endOfMonth()->format('Y-m-d');

            $monthWeekDay = $yearMonth->startOfMonth()->endOfWeek()->format('Y-m-d');

            $user->points()->where(['date' => $monthLastDay, 'data_source_id' => 1, 'event_id' => $event->id, 'modality' => 'other'])->delete();
            $user->points()->create(['amount' => $monthMile, 'date' => $monthLastDay, 'data_source_id' => 1, 'event_id' => $event->id, 'modality' => 'other']);

            $user->weeklyPoints()->where(['date' => $monthWeekDay, 'event_id' => $event->id])->delete();
            $user->weeklyPoints()->create(['amount' => $monthMile, 'date' => $monthWeekDay, 'event_id' => $event->id]);

            $user->monthlyPoints()->where(['date' => $monthLastDay, 'event_id' => $event->id])->delete();
            $user->monthlyPoints()->create(['amount' => $monthMile, 'date' => $monthLastDay, 'event_id' => $event->id]);
        }
    }

    public function userMileStatics($event, $user)
    {

        $eventSlug = Str::slug($event->name);

        $participation = $user->participations()->where('event_id', $event->id)->first();

        $settings = json_decode($user->settings, true);

        $rtyGoals = (isset($settings['rty_goals'])) ? $settings['rty_goals'] : [];

        $rtyGoal = collect($rtyGoals)->filter(function ($goal) use ($eventSlug) {
            return in_array($eventSlug, array_keys($goal));
        })
            ->pluck($eventSlug)->first();

        $distance = $rtyGoal;

        if (!$rtyGoal) {
            $distance = $event->total_points;
        }
        $userPoint = $user->points()->selectRaw("SUM(amount) AS total_mile")->where('event_id', $event->id)->where('date', '>=', Carbon::parse($event->start_date)->format('Y-m-d'))->where('date', '<=', Carbon::now()->format('Y-m-d'))->first();

        $userTotalPoints = $userPoint->total_mile ? $userPoint->total_mile : 0;

        $completedPercentage = ($userTotalPoints * 100) / ($distance ? $distance : 1);

        $remainingDistance = $distance - $userTotalPoints;

        return [
            'total_miles' => number_format($distance, 2, '.', ''),
            'completed_miles' => number_format($userTotalPoints, 2, '.', ''),
            'remaining_miles' => number_format($remainingDistance, 2, '.', ''),
            'completed_percentage' => number_format($completedPercentage, 2, '.', '')
        ];
    }

    private function calculateUserTotal($user, $event, $startDate, $endDate)
    {

        return $user->points()->where(['event_id' => $event->id])->where('date', '>=', $startDate)->where('date', '<=', $endDate)->sum('amount');
    }

    public function calculateUserWeeklyPoints($user, $event, $date = null)
    {

        $eventStartDate = CarbonImmutable::parse($event->start_date);
        $eventEndDate = CarbonImmutable::parse($event->end_date);

        $currentDateTime = $date ? CarbonImmutable::parse($date) : CarbonImmutable::now();


        $endDate = $currentDateTime->endOfWeek();
        $startDate = $currentDateTime->startOfWeek();

        if ($startDate->lt($eventStartDate)) {
            $startDate = $eventStartDate;
        }

        if ($endDate->gt($eventEndDate)) {
            $endDate = $eventEndDate;
        }

        $endDate = $endDate->format('Y-m-d');
        $startDate = $startDate->format('Y-m-d');

        /*
        //->format('Y-m-d')

        $startDate = $eventStartDate->format('Y-m-d');
       
        if($currentDateTime->lt($eventStartDate) || $currentDateTime->gt($eventEndDate)) {
            $endDate = $eventEndDate->endOfWeek()->format('Y-m-d');
            $startDate = $eventEndDate->startOfWeek()->format('Y-m-d');
        } else {
            $endDate = $currentDateTime->endOfWeek()->format('Y-m-d');
            $startDate = $currentDateTime->startOfWeek()->format('Y-m-d');
        }
        */
        $points = $this->calculateUserTotal($user, $event, $startDate, $endDate);

        return [$points, $endDate];
    }

    public function calculateUserMonthlyPoints($user, $event, $date = null)
    {

        $eventStartDate = CarbonImmutable::parse($event->start_date);
        $eventEndDate = CarbonImmutable::parse($event->end_date);

        $currentDateTime = $date ? CarbonImmutable::parse($date) : CarbonImmutable::now();


        $endDate = $currentDateTime->endOfMonth();
        $startDate = $currentDateTime->startOfMonth();

        if ($startDate->lt($eventStartDate)) {
            $startDate = $eventStartDate;
        }

        if ($endDate->gt($eventEndDate)) {
            $endDate = $eventEndDate;
        }

        $endDate = $endDate->format('Y-m-d');
        $startDate = $startDate->format('Y-m-d');

        /*
        $startDate = $eventStartDate->format('Y-m-d');
       
        if($currentDateTime->lt($eventStartDate) || $currentDateTime->gt($eventEndDate)) {
            $endDate = $eventEndDate->endOfMonth()->format('Y-m-d');
            $startDate = $eventEndDate->startOfMonth()->format('Y-m-d');
        } else {
            $endDate = $currentDateTime->endOfMonth()->format('Y-m-d');
            $startDate = $currentDateTime->startOfMonth()->format('Y-m-d');
        }
        */
        $points = $this->calculateUserTotal($user, $event, $startDate, $endDate);

        return [$points, $endDate];
    }

    public function calculateUserTotalPoints($user, $event)
    {

        $startDate = CarbonImmutable::parse($event->start_date)->format('Y-m-d');
        $endDate = CarbonImmutable::parse($event->end_date)->format('Y-m-d');

        $points = $this->calculateUserTotal($user, $event, $startDate, $endDate);

        return [$points, $endDate];
    }

    public function createOrUpdateUserPoint($user, $event, $date = null)
    {

        if (is_numeric($event)) {
            $event = Event::find($event);
        }

        list($weeklyPoints, $weekDay) = $this->calculateUserWeeklyPoints($user, $event, $date);
        list($monthlyPoints, $monthDay) = $this->calculateUserMonthlyPoints($user, $event, $date);
        list($totalPoints, $day) = $this->calculateUserTotalPoints($user, $event);

        $monthlyPoint = $user->monthlyPoints()->where(['date' => $monthDay, 'event_id' => $event->id])->first();

        $weeklyPoint = $user->weeklyPoints()->where(['date' => $weekDay, 'event_id' => $event->id])->first();

        //$totalPoint = $user->totalPoints()->where(['date' => $day,'event_id' => $event->id])->first();
        $totalPoint = $user->totalPoints()->where(['event_id' => $event->id])->first();

        if ($monthlyPoint) {
            $monthlyPoint->fill(['amount' => $monthlyPoints])->save();
        } else {
            $user->monthlyPoints()->create(['date' => $monthDay, 'event_id' => $event->id, 'amount' => $monthlyPoints]);
        }

        if ($weeklyPoint) {
            $weeklyPoint->fill(['amount' => $weeklyPoints])->save();
        } else {
            $user->weeklyPoints()->create(['date' => $weekDay, 'event_id' => $event->id, 'amount' => $weeklyPoints]);
        }

        if ($totalPoint) {
            $totalPoint->fill(['amount' => $totalPoints])->save();
        } else {
            // $user->totalPoints()->create(['date' => $day,'event_id' => $event->id,'amount' => $totalPoints]);
            $user->totalPoints()->create(['event_id' => $event->id, 'amount' => $totalPoints]);
        }

        $bestMonth = $user->monthlyPoints()->where(['event_id' => $event->id])->latest('amount')->first();
        $bestWeek = $user->weeklyPoints()->where(['event_id' => $event->id])->latest('amount')->first();
        $bestDay = $user->points()->where(['event_id' => $event->id])->latest('amount')->first();

        if ($bestMonth) { //'accomplishment','date','achievement'
            $user->achievements()->where('achievement', 'best_month')->where('event_id', $event->id)->delete();
            $user->achievements()->create(['achievement' => 'best_month', 'event_id' => $event->id, 'accomplishment' => $bestMonth->amount, 'date' => $bestMonth->date]);
        }

        if ($bestWeek) {
            $user->achievements()->where('achievement', 'best_week')->where('event_id', $event->id)->delete();
            $user->achievements()->create(['achievement' => 'best_week', 'event_id' => $event->id, 'accomplishment' => $bestWeek->amount, 'date' => $bestWeek->date]);
        }

        if ($bestDay) {
            $user->achievements()->where('achievement', 'best_day')->where('event_id', $event->id)->delete();
            $user->achievements()->create(['achievement' => 'best_day', 'event_id' => $event->id, 'accomplishment' => $bestDay->amount, 'date' => $bestDay->date]);
        }

        $this->updateTeamPoint($user, $event, $date);
    }

    public function updateTeamPoint($user, $event, $date = null)
    {

        if (is_numeric($event)) {
            $event = Event::find($event);
        }

        $team = Team::whereHas('memberships', function ($query) use ($user, $event) {
            return $query->where('user_id', $user->id)->where('event_id', $event->id);
        })->first();

        if (is_null($team)) {
            return false;
        }

        $points = UserPoint::selectRaw("SUM(amount) AS total_amount, date")->where(function ($query) use ($date) {

            if ($date) {
                return $query->where('date', $date);
            }

            return $query;
        })->whereIn('user_id', $team->memberships()->pluck('user_id')->toArray())->where('event_id', $event->id)->groupBy('date')->get();

        foreach ($points as $point) {
            $teamPoint = $team->points()->where('event_id', $event->id)->where('date', $point->date)->first();

            if ($teamPoint) {
                $teamPoint->fill(['amount' => $point->total_amount])->save();
            } else {
                $team->points()->create(['event_id' => $event->id, 'date' => $point->date, 'amount' => $point->total_amount]);
            }
        }

        $eventStartDate = CarbonImmutable::parse($event->start_date);

        $eventEndDate = CarbonImmutable::parse($event->end_date);

        $currentDateTime = $date ? CarbonImmutable::parse($date) : CarbonImmutable::now();

        $startDate = $eventStartDate->format('Y-m-d');
        $endDate = $eventEndDate->format('Y-m-d');

        /*
        $weekEndDate = $eventEndDate->endOfWeek()->format('m-d-Y');
            $monthEndDate = $eventEndDate->endOfMonth()->format('m-d-Y');
        */
        /*
       
        if($currentDateTime->lt($eventStartDate) || $currentDateTime->gt($eventEndDate)) {
            $weekEndDate = $eventEndDate->endOfWeek()->format('m-d-Y');
            $monthEndDate = $eventEndDate->endOfMonth()->format('m-d-Y');
            
            $weekStartDate = $eventEndDate->startOfWeek()->format('m-d-Y');
            $monthStartDate = $eventEndDate->startOfMonth()->format('m-d-Y');
        } else {
            $weekEndDate = $currentDateTime->endOfWeek()->format('m-d-Y');
            $monthEndDate = $currentDateTime->endOfMonth()->format('m-d-Y');
            
            $weekStartDate = $currentDateTime->startOfWeek()->format('m-d-Y');
            $monthStartDate = $currentDateTime->startOfMonth()->format('m-d-Y');
        }*/

        $weekEndDate = $currentDateTime->endOfWeek();
        $weekStartDate = $currentDateTime->startOfWeek();

        $monthEndDate = $currentDateTime->endOfMonth();
        $monthStartDate = $currentDateTime->startOfMonth();

        if ($monthStartDate->lt($eventStartDate)) {
            $monthStartDate = $eventStartDate;
        }

        if ($monthEndDate->gt($eventEndDate)) {
            $monthEndDate = $eventEndDate;
        }

        if ($weekStartDate->lt($eventStartDate)) {
            $weekStartDate = $eventStartDate;
        }

        if ($weekEndDate->gt($eventEndDate)) {
            $weekEndDate = $eventEndDate;
        }

        $weekEndDate = $weekEndDate->format('Y-m-d');
        $weekStartDate = $weekStartDate->format('Y-m-d');

        $monthEndDate = $monthEndDate->format('Y-m-d');
        $monthStartDate = $monthStartDate->format('Y-m-d');

        $teamWeeklyTotal = $team->points()->where(['event_id' => $event->id])->where('date', '>=', $weekStartDate)->where('date', '<=', $weekEndDate)->sum('amount');
        $teamMontlyTotal = $team->points()->where(['event_id' => $event->id])->where('date', '>=', $monthStartDate)->where('date', '<=', $monthEndDate)->sum('amount');
        $teamTotal = $team->points()->where(['event_id' => $event->id])->where('date', '>=', $startDate)->where('date', '<=', $endDate)->sum('amount');

        $monthlyPoint = $team->monthlyPoints()->where(['date' => $monthEndDate, 'event_id' => $event->id])->first();

        $weeklyPoint = $team->weeklyPoints()->where(['date' => $weekEndDate, 'event_id' => $event->id])->first();

        $totalPoint = $team->totalPoints()->where(['event_id' => $event->id])->first();

        if ($monthlyPoint) {
            $monthlyPoint->fill(['amount' => $teamMontlyTotal])->save();
        } else {
            $team->monthlyPoints()->create(['date' => $monthEndDate, 'event_id' => $event->id, 'amount' => $teamMontlyTotal]);
        }

        if ($weeklyPoint) {
            $weeklyPoint->fill(['amount' => $teamWeeklyTotal])->save();
        } else {
            $team->weeklyPoints()->create(['date' => $weekEndDate, 'event_id' => $event->id, 'amount' => $teamWeeklyTotal]);
        }

        if ($totalPoint) {
            $totalPoint->fill(['amount' => $teamTotal])->save();
        } else {
            $team->totalPoints()->create(['event_id' => $event->id, 'amount' => $teamTotal]);
        }

        $bestMonth = $team->monthlyPoints()->where(['event_id' => $event->id])->latest('amount')->first();
        $bestWeek = $team->weeklyPoints()->where(['event_id' => $event->id])->latest('amount')->first();
        $bestDay = $team->points()->where(['event_id' => $event->id])->latest('amount')->first();


        if ($bestMonth) { //'accomplishment','date','achievement'
            $team->achievements()->where('achievement', 'best_month')->where('event_id', $event->id)->delete();
            $team->achievements()->create(['achievement' => 'best_month', 'event_id' => $event->id, 'accomplishment' => $bestMonth->amount, 'date' => $bestMonth->date]);
        }

        if ($bestWeek) {
            $team->achievements()->where('achievement', 'best_week')->where('event_id', $event->id)->delete();
            $team->achievements()->create(['achievement' => 'best_week', 'event_id' => $event->id, 'accomplishment' => $bestWeek->amount, 'date' => $bestWeek->date]);
        }

        if ($bestDay) {
            $team->achievements()->where('achievement', 'best_day')->where('event_id', $event->id)->delete();
            $team->achievements()->create(['achievement' => 'best_day', 'event_id' => $event->id, 'accomplishment' => $bestDay->amount, 'date' => $bestDay->date]);
        }
    }

    public function deleteSourceSyncedMile($user, $dataSourceId)
    {
        $participations = $user->participations()->get();

        foreach ($participations as $participation) {
            $user->points()->where('event_id', $participation->event->id)->where('data_source_id', $dataSourceId)->delete();
            $this->createOrUpdateUserPoint($user, $participation->event);
        }
    }

    public function createUserParticipationPoints($user, $point)
    {
        $participations = $this->userParticipations($user, $point['date']);

        if (!$participations) {
            return false;
        }

        foreach ($participations as $participation) {
            $point['eventId'] = $participation['event_id'];
            $this->createOrUpdate($user, $point);
            $this->createOrUpdateUserPoint($user, $participation->event_id, $point['date']);
        }
    }

    public function createOrUpdate(object $user, array $point, bool $skipUpdate = false)
    {
        $point['date'] = Carbon::parse($point['date'])
            ->setTimezone($user->time_zone ?? 'UTC');

        $condition = $skipUpdate ? [] : [
            'date' => $point['date'],
            'modality' => $point['modality'],
            'event_id' => $point['eventId'],
            'data_source_id' => $point['dataSourceId']
        ];

        $this->userPointRepository->create($user, $point, $condition);
    }
}
