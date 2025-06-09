<div class="d-inline-block">
    <div class="d-inline-block">
        <a href="javascript:;" class="btn btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="icon-base ti tabler-dots-vertical"></i></a>
        <div class="dropdown-menu dropdown-menu-end m-0">
            <a href="{{ route('admin.events.streaks.edit', [$event->id,$item->id]) }}" class="dropdown-item waves-effect"><i
                    class="icon-base ti tabler-pencil mb-2"></i> Edit Streak</a>
            <button class="dropdown-item waves-effect text-danger action-delete" data-confirm-form="#activity-action-delete-form-{{ $item->id }}">
                <i class="icon-base ti tabler-trash me-1"></i> Delete Streak
            </button>
            <form method="POST" id="activity-action-delete-form-{{ $item->id }}" action="{{ route('admin.events.streaks.delete', [$item->event_id,$item->id]) }}">
                @method('DELETE')
                @csrf
            </form>
        </div>
    </div>
</div>
