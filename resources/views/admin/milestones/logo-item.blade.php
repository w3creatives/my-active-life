@php
    $viewType = $type??'list';
@endphp
@if($image_url)
    @if($viewType == 'list')
        <li data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-placement="top" class="avatar pull-up"
            aria-label="Vinnie Mostowy" data-bs-original-title="{{ $title }}">
            <a href="{{ route('admin.events.milestones.view', [$item->event_id,$item->id]) }}" data-bs-toggle="modal"
               data-bs-target="#view-milestone-modal">
                <img class="rounded-circle" src="{{ $image_url }}" alt="{{ $title }}" onerror="this.src='{{ url('/images/default-placeholder.png') }}'">
            </a>
        </li>
    @else
        <div class="col-6 mt-3">
            <h5>{{ $title }}</h5>
            <p class="card-text">
            <div class="mx-auto my-6">
                <img src="{{ $image_url }}" alt="{{ $title }}"
                     class="img-fluid" onerror="this.src='{{ url('/images/default-placeholder.png') }}'"/>
            </div>
            </p>
        </div>
    @endif
@endif
