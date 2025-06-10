<?php

namespace App\View\Components\Event;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ListAction extends Component
{
    public string $addActionUrl;
    public string $addActionTitle = 'Milestone';

    public function __construct(public $event, public $activity = null)
    {
        switch ($event->event_type) {
            case 'regular':
            case 'month':
                $this->addActionUrl = route('admin.events.milestones.create', $event->id);
                break;
            case 'fit_life':
                $this->addActionUrl = route('admin.events.activity.milestones.create', [$event->id, $activity->id]);
                break;
            case 'promotional':
                $this->addActionUrl = route('admin.events.streaks.create', $event->id);
                $this->addActionTitle = 'Streak';
                break;
        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.event.list-action');
    }
}
