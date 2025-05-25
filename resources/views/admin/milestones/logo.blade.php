<div class="d-flex align-items-center flex-wrap">
    @if($item->logo)
        <div class="avatar avatar-md me-2">
            <img src="{{ $item->logo }}" alt="Avatar" class="rounded-circle" data-bs-toggle="tooltip" data-bs-placement="top" title="Logo">
        </div>
    @endif
        @if($item->team_logo)
            <div class="avatar avatar-md me-2">
                <img src="{{ $item->team_logo }}" alt="Avatar" class="rounded-circle" title="Team Logo">
            </div>
        @endif
</div>
