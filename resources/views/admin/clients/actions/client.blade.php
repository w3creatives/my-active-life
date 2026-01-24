<div class="flex justify-content-between">
    <a href="{{ route('admin.clients.edit', $client->id) }}" class="btn btn-icon btn-text-secondary waves-effect"><i
            class="icon-base ti tabler-pencil" data-bs-toggle="tooltip" title="Edit"></i> </a>
    <a href="{{ route('admin.clients.view', $client->id) }}" class="btn btn-icon btn-text-secondary waves-effect"><i
            class="icon-base ti tabler-eye" data-bs-toggle="tooltip" title="View Details"></i> </a>
</div>
