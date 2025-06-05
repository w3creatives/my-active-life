<div class="d-md-flex justify-content-between align-items-center dt-layout-end col-md-auto ms-auto mt-0">
    <div class="dt-buttons btn-group flex-wrap mb-0">
        @switch($event->event_type)
            @case('regular')
                <a href="{{ route('admin.events') }}" class="btn btn-label-primary me-4">Back to Events</a>
                @break
            @case('fit_life')
                <a href="{{ route('admin.events.activity.milestones', [$event->id, $activity->id]) }}" class="btn btn-label-primary me-4">Back to Activities</a>
                @break
        @endswitch

        <a href="{{ $addMilestoneUrl }}" class="btn create-new btn-primary">
            <span>
                <span class="d-flex align-items-center gap-2">
                    <i class="icon-base ti tabler-plus icon-sm"></i>
                    <span class="d-none d-sm-inline-block">Add New Milestone</span>
                </span>
            </span>
        </a>
    </div>
</div>
