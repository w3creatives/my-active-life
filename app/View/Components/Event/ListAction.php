<?php

namespace App\View\Components\Event;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ListAction extends Component
{
    public string $addMilestoneUrl;
    public function __construct(public $event, public $activity)
    {
        switch ($event->event_type) {
            case 'regular':
                $this->addMilestoneUrl = route('admin.events.milestones.create',$event->id);
                break;
            case 'fit_life':
                $this->addMilestoneUrl = route('admin.events.activity.milestones.create', [$event->id, $activity->id]);
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
