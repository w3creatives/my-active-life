<div class="d-flex justify-between">
    <a href="{{ $event->event_type == 'fit_life'?route('admin.events.activity.milestones.edit',[$event->id,$item->activity_id,$item->id]):route('admin.events.milestones.edit', [$event->id,$item->id]) }}"
       class="btn btn-icon btn-text-secondary waves-effect" data-bs-toggle="tooltip" title="Edit Milestone"><i
            class="icon-base ti tabler-pencil mb-2"></i></a>
    @if(in_array($event->event_type,['regular','month']))
        <a href="{{ route('admin.events.milestones.view', [$event->id,$item->id]) }}"
           class="btn btn-icon btn-text-secondary waves-effect" data-bs-toggle="modal"
           data-bs-target="#view-milestone-modal" ><i data-bs-toggle="tooltip" title="View Details" class="icon-base ti tabler-target mb-2"></i></a>
    @endif
</div>
