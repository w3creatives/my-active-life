<div class="flex justify-content-between">
    <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-icon btn-text-secondary waves-effect"><i
            class="icon-base ti tabler-pencil" data-bs-toggle="tooltip" title="Edit"></i> </a>
    <a href="{{ route('impersonate', ['id' => $user->id]) }}" class="btn btn-icon btn-text-secondary waves-effect" data-bs-toggle="tooltip" title="Login as User"><i
            class="icon-base ti tabler-login"></i></a>
</div>
