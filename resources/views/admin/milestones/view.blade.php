<div class="card-body">
    <div class="row">

            <div class="col">
                <h5 class="card-title">{{ $event->is_fit_life_event?'Activity ':'' }}Name</h5>
                <p class="card-text">{{ $event->is_fit_life_event?$eventMilestone->activity->name:$eventMilestone->name }}</p>
            </div>
            <div class="col-auto">
                <a title="Edit Milestone"
                   href="{{ $event->is_fit_life_event?route('admin.events.activity.milestones.edit',[$event->id,$eventMilestone->activity_id,$eventMilestone->id]):route('admin.events.milestones.edit', [$eventMilestone->event_id,$eventMilestone->id]) }}"><i
                        class="icon-base ti tabler-pencil icon-22px text-body-dark"></i></a>

            </div>
            <div class="col-12  mt-3">
                <h5 class="card-title">Distance</h5>
                <p class="card-text">{{ $event->is_fit_life_event?$eventMilestone->total_points:$eventMilestone->distance }}</p>

            </div>
            @if(!$event->is_fit_life_event)
            <div class="col-12 mt-3">
                <h5 class="card-title">Description</h5>
                <p class="card-text">{{ $eventMilestone->description }}</p>
            </div>
            @endif
            <div class="col-12 mt-3">
                <h5 class="card-title">Data</h5>
                <p class="card-text">{{ $eventMilestone->video_url }}</p>
            </div>

        @include('admin.milestones.logo-item',['title' => 'Logo', 'item' => $eventMilestone, 'image_url' => $eventMilestone->logo, 'type' => 'view'])
        @include('admin.milestones.logo-item',['title' => 'Calendar Logo', 'item' => $eventMilestone, 'image_url' => $eventMilestone->calendar_logo, 'type' => 'view'])

        @if(in_array($event->event_type, ['regular', 'month']))
            @include('admin.milestones.logo-item',['title' => 'Team Logo', 'item' => $eventMilestone, 'image_url' => $eventMilestone->team_logo, 'type' => 'view'])

            @include('admin.milestones.logo-item',['title' => 'Calendar Team Logo', 'item' => $eventMilestone, 'image_url' => $eventMilestone->calendar_team_logo, 'type' => 'view'])
            @include('admin.milestones.logo-item',['title' => 'Team Bibs Image', 'item' => $eventMilestone, 'image_url' => $eventMilestone->team_bib_image, 'type' => 'view'])
        @else
            @include('admin.milestones.logo-item',['title' => 'BW Logo', 'item' => $eventMilestone, 'image_url' => $eventMilestone->bw_logo, 'type' => 'view'])
            @include('admin.milestones.logo-item',['title' => 'BW Calendar Logo', 'item' => $eventMilestone, 'image_url' => $eventMilestone->bw_calendar_logo, 'type' => 'view'])
            @include('admin.milestones.logo-item',['title' => 'Bibs Image', 'item' => $eventMilestone, 'image_url' => $eventMilestone->bib_image, 'type' => 'view'])
        @endif
    </div>
</div>
