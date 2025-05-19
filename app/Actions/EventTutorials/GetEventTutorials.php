<?php

declare(strict_types=1);

namespace App\Actions\EventTutorials;

use App\Models\Event;
use App\Models\EventTutorial;
use Illuminate\Validation\Rule;
use Lorisleiva\Actions\Concerns\AsAction;

final class GetEventTutorials
{
    use AsAction;

    public function rules(): array
    {
        return [
            'event_id' => [
                'required',
                Rule::exists(Event::class, 'id'),
            ],
        ];
    }

    public function handle($data)
    {
        return EventTutorial::where('event_id', $data['event_id'])
            ->get()
            ->map(function ($tutorial) {
                return [
                    'event_id' => $tutorial->event_id,
                    'tutorial_text' => $tutorial->tutorial_text,
                ];
            })
            ->first();
    }
}
