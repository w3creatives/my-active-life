<div class="d-inline-block">
    <div class="d-inline-block">
        <a href="javascript:;" class="btn btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="icon-base ti tabler-dots-vertical"></i></a>
        <div class="dropdown-menu dropdown-menu-end m-0">
             <a href="{{ route('admin.events.activities.create', [$item->event_id,$item->id]) }}" class="dropdown-item waves-effect"><i
                    class="icon-base ti tabler-pencil mb-2"></i> Edit Activity</a>
            <a href="{{ route('admin.events.milestones.view', [$item->event_id,$item->id]) }}" class="d-none dropdown-item waves-effect" data-bs-toggle="modal" data-bs-target="#view-milestone-modal">
                <i class="icon-base ti tabler-target mb-2"></i> View Details
            </a>

            <a href="{{ route('admin.events.activity.milestones', [$item->event_id, $item->id]) }}" class="dropdown-item waves-effect"><i
                    class="icon-base ti tabler-target mb-2"></i> Milestones</a>
            <button class="dropdown-item waves-effect text-danger action-delete" data-confirm-form="#activity-action-delete-form-{{ $item->id }}">
                <i class="icon-base ti tabler-trash me-1"></i> Delete Activity
            </button>
            <form method="POST" id="activity-action-delete-form-{{ $item->id }}" action="{{ route('admin.events.activities.delete', [$item->event_id,$item->id]) }}">
                @method('DELETE')
                @csrf
            </form>
        </div>
    </div>
</div>
