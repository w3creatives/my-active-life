<div class="btn-group" role="group">
    <button id="btnGroupDrop1" type="button" class="btn btn-outline-secondary dropdown-toggle waves-effect"
            data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <i class="icon-base ti tabler-dots-vertical icon-md d-sm-none"></i><span class="d-none d-sm-block">Action</span>
    </button>
    <div class="dropdown-menu" aria-labelledby="btnGroupDrop1" style="">
        <a href="{{ route('admin.events.edit', $event->id) }}" class="dropdown-item waves-effect"><i
                class="icon-base ti tabler-pencil mb-2"></i> Edit Event</a>
        <a href="{{ route('admin.events.milestones', $event->id) }}" class="dropdown-item waves-effect"
           ><i class="icon-base ti tabler-target mb-2"></i> Milestones</a>
    </div>
</div>
