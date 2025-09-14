<div class="flex justify-content-between">
    <a href="{{ route('admin.email.builders.edit',$item->id) }}" class="btn btn-icon btn-text-secondary waves-effect" data-bs-toggle="tooltip" title="Edit Template"><i
            class="icon-base ti tabler-pencil"></i></a>
    <a href="" class="d-none btn btn-icon btn-text-secondary waves-effect" data-bs-toggle="modal" data-bs-target="#view-email-template-modal">
        <i class="icon-base ti tabler-target" data-bs-toggle="tooltip" title="View Details"></i>
    </a>

    <a class="btn btn-icon btn-text-danger waves-effect text-danger action-delete" data-confirm-form="#activity-action-delete-form-{{ $item->id }}" data-bs-toggle="tooltip" title="Delete Activity">
        <i class="icon-base ti tabler-trash"></i>
    </a>
    <form method="POST" id="activity-action-delete-form-{{ $item->id }}" action="{{ route('admin.email.builders.destroy', $item->id) }}">
        @method('DELETE')
        @csrf
    </form>
</div>
