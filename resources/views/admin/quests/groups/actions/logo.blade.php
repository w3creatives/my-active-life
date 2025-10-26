<ul class="list-group list-group-flush">
    <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap border-top-0 p-0">
        <div class="d-flex flex-wrap align-items-center">
            <ul class="list-unstyled users-list d-flex align-items-center avatar-group m-0 me-2">
                <li data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-placement="top" class="avatar pull-up"
                    aria-label="Vinnie Mostowy" data-bs-original-title="{{ $item->name }}">
                    <a herf="javascript:;" data-item-logo="{{ $item->logo_url }}" data-bs-toggle="modal"
                       data-bs-target="#view-milestone-modal">
                        <img class="rounded-circle" src="{{ $item->logo_url }}" alt="{{ $item->name }}" onerror="this.src='{{ url('/images/default-placeholder.png') }}'">
                    </a>
                </li>
            </ul>
        </div>
    </li>
</ul>
