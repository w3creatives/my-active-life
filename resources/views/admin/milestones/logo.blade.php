@if($item->logo && $item->team_logo)
<ul class="list-group list-group-flush">
    <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap border-top-0 p-0">
        <div class="d-flex flex-wrap align-items-center">
            <ul class="list-unstyled users-list d-flex align-items-center avatar-group m-0 me-2">
                @if($item->logo)
                <li data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-placement="top" class="avatar pull-up" aria-label="Vinnie Mostowy" data-bs-original-title="Logo">
                    <a href="{{ route('admin.events.milestones.view', [$item->event_id,$item->id]) }}" data-bs-toggle="modal" data-bs-target="#view-milestone-modal">
                        <img class="rounded-circle" src="{{ $item->logo }}" alt="Avatar">
                    </a>
                </li>
                @endif
                @if($item->team_logo)
                <li data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-placement="top" class="avatar pull-up" aria-label="Allen Rieske" data-bs-original-title="Team Logo">
                    <a href="{{ route('admin.events.milestones.view', [$item->event_id,$item->id]) }}" data-bs-toggle="modal" data-bs-target="#view-milestone-modal">
                        <img class="rounded-circle" src="{{ $item->team_logo }}" alt="Avatar">
                    </a>
                </li>
                @endif
            </ul>
        </div>
    </li>
</ul>
@endif