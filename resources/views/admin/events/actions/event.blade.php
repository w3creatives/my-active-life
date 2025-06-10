<div class="d-inline-block">
    <div class="d-inline-block">
        <a href="javascript:;" class="btn btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="icon-base ti tabler-dots-vertical"></i></a>
        <div class="dropdown-menu dropdown-menu-end m-0">
            <a href="{{ route('admin.events.edit', $event->id) }}" class="dropdown-item waves-effect"><i
                    class="icon-base ti tabler-pencil mb-2"></i> Edit Event</a>
            @switch($event->event_type)
                @case('regular')
                @case('month')
                    <a href="{{ route('admin.events.milestones', $event->id) }}" class="dropdown-item waves-effect"><i
                            class="icon-base ti tabler-target mb-2"></i> Milestones</a>
                    @break
                @case('fit_life')
                    <a href="{{ route('admin.events.activities', $event->id) }}" class="dropdown-item waves-effect"><i
                            class="icon-base ti tabler-target mb-2"></i> Activities</a>
                    @break
                @case('promotional')
                    <a href="{{ route('admin.events.streaks', $event->id) }}" class="dropdown-item waves-effect"><i
                            class="icon-base ti tabler-target mb-2"></i> Streaks</a>
                    @break
            @endswitch
        </div>
    </div>
</div>
