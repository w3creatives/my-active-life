
<div class="d-flex justify-between">
    <a href="{{ route('admin.events.edit', $event->id) }}" class="btn btn-icon btn-text-secondary waves-effect" data-bs-toggle="tooltip" title="Edit Event"><i
            class="icon-base ti tabler-pencil mb-2"></i></a>
    @switch($event->event_type)
        @case('regular')
        @case('month')
            <a href="{{ route('admin.events.milestones', $event->id) }}" class="btn btn-icon btn-text-secondary waves-effect" data-bs-toggle="tooltip" title="Milestones"><i
                    class="icon-base ti tabler-target mb-2"></i></a>
            @break
        @case('fit_life')
            <a href="{{ route('admin.events.activities', $event->id) }}" class="btn btn-icon btn-text-secondary waves-effect" data-bs-toggle="tooltip" title="Activities"><i
                    class="icon-base ti tabler-target mb-2"></i></a>
            @break
        @case('promotional')
            <a href="{{ route('admin.events.streaks', $event->id) }}" class="btn btn-icon btn-text-secondary waves-effect" data-bs-toggle="tooltip" title="Streaks"><i
                    class="icon-base ti tabler-target mb-2"></i></a>
            @break
    @endswitch
    <a href="{{ route('admin.events.tutorials', $event->id) }}" class="btn btn-icon btn-text-secondary waves-effect" data-bs-toggle="tooltip" title="Tutorials"><i
            class="icon-base ti tabler-book mb-2"></i></a>
</div>


