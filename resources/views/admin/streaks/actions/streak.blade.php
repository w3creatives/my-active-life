<div class="flex justify-content-between">
    <a href="{{ route('admin.events.streaks.edit', [$event->id,$item->id]) }}" class="btn btn-icon btn-text-secondary waves-effect"><i
            class="icon-base ti tabler-pencil" data-bs-toggle="tooltip" title="Edit Streak"></i></a>
    <a class="btn btn-icon btn-text-danger waves-effect text-danger action-delete" data-confirm-form="#activity-action-delete-form-{{ $item->id }}" data-bs-toggle="tooltip" title="Delete Streak">
        <i class="icon-base ti tabler-trash me-1"></i>
    </a>
    <form method="POST" id="activity-action-delete-form-{{ $item->id }}" action="{{ route('admin.events.streaks.delete', [$item->event_id,$item->id]) }}">
        @method('DELETE')
        @csrf
    </form>
</div>
