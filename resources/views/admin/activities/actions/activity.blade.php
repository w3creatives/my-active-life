<div class="flex justify-content-between">
    <a href="{{ route('admin.events.activities.create', [$item->event_id,$item->id]) }}" class="btn btn-icon btn-text-secondary waves-effect"><i
            class="icon-base ti tabler-pencil " data-bs-toggle="tooltip" title="Edit Activity"></i> </a>
    <a href="{{ route('admin.events.milestones.view', [$item->event_id,$item->id]) }}" class="d-none btn btn-icon btn-text-secondary waves-effect" data-bs-toggle="modal" data-bs-target="#view-milestone-modal">
        <i class="icon-base ti tabler-target " data-bs-toggle="tooltip" title="View Details"></i>
    </a>

    <a href="{{ route('admin.events.activity.milestones', [$item->event_id, $item->id]) }}" class="btn btn-icon btn-text-secondary waves-effect"><i
            class="icon-base ti tabler-target " data-bs-toggle="tooltip" title="Milestones"></i> </a>
    <a class="btn btn-icon btn-text-danger waves-effect text-danger action-delete" data-confirm-form="#activity-action-delete-form-{{ $item->id }}">
        <i class="icon-base ti tabler-trash me-1" data-bs-toggle="tooltip" title="Delete Activity"></i>
    </a>
    <form method="POST" id="activity-action-delete-form-{{ $item->id }}" action="{{ route('admin.events.activities.delete', [$item->event_id,$item->id]) }}">
        @method('DELETE')
        @csrf
    </form>
</div>
