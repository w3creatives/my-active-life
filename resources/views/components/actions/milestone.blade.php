<div class="d-inline-block">
    <div class="d-inline-block">
        <a href="javascript:;" class="btn btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="icon-base ti tabler-dots-vertical"></i></a>
        <div class="dropdown-menu dropdown-menu-end m-0">
             <a href="{{ route('admin.events.milestones.edit', [$event->id,$item->id]) }}" class="dropdown-item waves-effect"><i
                    class="icon-base ti tabler-pencil mb-2"></i> Edit Milestone</a>
            <a href="{{ route('admin.events.milestones.view', [$event->id,$item->id]) }}" class="dropdown-item waves-effect" data-bs-toggle="modal" data-bs-target="#view-milestone-modal"><i class="icon-base ti tabler-target mb-2"></i> View Details</a>
        </div>
    </div>
</div>
