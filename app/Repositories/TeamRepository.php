<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Team;

final class TeamRepository
{
    public function find($id)
    {
        return Team::find($id);
    }

    public function achievements($event, $dateRange, $team)
    {
        $eventId = $event->id;

        [$today, $startOfMonth, $endOfMonth, $startOfWeek, $endOfWeek] = $dateRange;

        $achievements = $team->achievements()->select(['accomplishment', 'date', 'achievement'])->hasEvent($eventId)->latest('accomplishment')->get()->groupBy('achievement');

        $dayPoint = $team->points()->where('event_id', $eventId)->where('date', $today)->sum('amount');
        $weekPoint = $team->points()->where('event_id', $eventId)->where('date', '>=', $startOfWeek)->where('date', '<=', $endOfWeek)->sum('amount');
        $monthPoint = $team->points()->where('event_id', $eventId)->where('date', '>=', $startOfMonth)->where('date', '<=', $endOfMonth)->sum('amount');

        $achievements = $team->achievements()->select(['accomplishment', 'date', 'achievement'])->hasEvent($eventId)->latest('accomplishment')->get();

        $yearwisePoints = $team->points()->selectRaw('SUM(amount) miles,extract(year from "date") as year')->where('event_id', $eventId)->groupBy('year')->orderBy('year', 'DESC')->get();

        $totalPoints = $team->points()->where('event_id', $eventId)->sum('amount');

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

        $users = $team->memberships()->where('event_id', $eventId)->with('user')->get();

        $users = $users->map(function ($item) use ($achievementData, $eventId, $today, $startOfWeek, $endOfWeek, $startOfMonth, $endOfMonth) {
            $user = $item->user->only(['id', 'display_name']);

            $dayPoint = $item->user->points()->where('event_id', $eventId)->where('date', $today)->sum('amount');
            $weekPoint = $item->user->points()->where('event_id', $eventId)->where('date', '>=', $startOfWeek)->where('date', '<=', $endOfWeek)->sum('amount');
            $monthPoint = $item->user->points()->where('event_id', $eventId)->where('date', '>=', $startOfMonth)->where('date', '<=', $endOfMonth)->sum('amount');
            $achievements = $item->user->achievements()->select(['accomplishment', 'date', 'achievement'])->hasEvent($eventId)->latest('accomplishment')->get();

            $data = $achievementData;

            if ($achievements->count()) {
                foreach ($achievements as $achievement) {
                    $data[$achievement->achievement]['accomplishment'] = $achievement->accomplishment;
                    $data[$achievement->achievement]['date'] = $achievement->date;
                }
            }

            $data['current_day'] = [
                'achievement' => 'day',
                'accomplishment' => $dayPoint,
                'date' => $today,
            ];

            $data['current_week'] = [
                'achievement' => 'week',
                'accomplishment' => $weekPoint,
                'date' => $endOfWeek,
            ];

            $data['current_month'] = [
                'achievement' => 'month',
                'accomplishment' => $monthPoint,
                'date' => $endOfMonth,
            ];

            $user['achievement'] = $data;

            return $user;
        });

        return [$users, $totalPoints, $yearwisePoints];
    }

    public function leaveTeam($team, $user)
    {
        $team->memberships()->where(['user_id' => $user->id])->delete();

        if (!$team->memberships()->count()) {
            $message = sprintf('You are the last one to leave team %s. Successfully deleted team %s.', $team->name, $team->name);
            $this->deleteTeamForeignData($team->id);
            $team->delete();

            return $message;
        }

        if ($team->owner_id === $user->id) {
            $member = $team->memberships()->first();
            $team->fill(['owner_id' => $member->user_id]);

            return 'Team admin has been reassigned';
        }

        return 'You have successfully left your team.';
    }

    private function deleteTeamForeignData($teamId): void
    {
        $tables = DB::select("select table_name from information_schema.columns where column_name = 'team_id'");

        foreach ($tables as $table) {
            DB::table($table->table_name)->where('team_id', $teamId)->delete();
        }
    }
}
