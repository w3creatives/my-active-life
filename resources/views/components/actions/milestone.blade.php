<div class="btn-group" role="group">
    <button id="btnGroupDrop1" type="button" class="btn btn-outline-secondary dropdown-toggle waves-effect"
            data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <i class="icon-base ti tabler-dots-vertical icon-md d-sm-none"></i><span class="d-none d-sm-block">Action</span>
    </button>
    <div class="dropdown-menu" aria-labelledby="btnGroupDrop1" style="">
        <a href="{{ route('admin.events.milestones.edit', [$item->event_id,$item->id]) }}" class="dropdown-item waves-effect"><i
                class="icon-base ti tabler-pencil mb-2"></i> Edit Milestone</a>
        <a href="{{ route('admin.events.milestones.view', [$item->event_id,$item->id]) }}" class="dropdown-item waves-effect" data-bs-toggle="modal" data-bs-target="#view-milestone-modal"
        ><i class="icon-base ti tabler-target mb-2"></i> View Details</a>
    </div>
</div>
