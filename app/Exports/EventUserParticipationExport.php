<?php

namespace App\Exports;

use App\Models\EventParticipation;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class EventUserParticipationExport implements FromQuery, WithMapping,WithHeadings, ShouldAutoSize
{
    use Exportable;

    public function __construct(int $eventId)
    {
        $this->eventId = $eventId;

        return $this;
    }

    public function headings(): array
    {
        return [
            'First Name',
            'Last Name',
            'Email',
            'Start Date',
            'End Date',
            'Total Points',
        ];
    }

    public function map($participation): array
    {

        $user = $participation->user;
        $totalPoints = $user->totalPoints()->where('event_id', $participation->event_id)->sum('amount');

        return [
            $user->first_name,
            $user->last_name,
            $user->email,
            $participation->subscription_start_date,
            $participation->subscription_end_date,
            $totalPoints,
        ];
    }

    public function query()
    {
        return EventParticipation::query()->where('event_id', $this->eventId);
    }
}
