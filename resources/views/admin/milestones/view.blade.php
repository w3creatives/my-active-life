    <div class="card-body">
        <div class="row">
            <div class="col">
                <h5 class="card-title">Name</h5>
                <p class="card-text">{{ $eventMilestone->name }}</p>
            </div>
            <div class="col-auto">
                <a title="Edit Milestone" href="{{ route('admin.events.milestones.edit', [$eventMilestone->event_id,$eventMilestone->id]) }}"><i class="icon-base ti tabler-pencil icon-22px text-body-dark"></i></a>
    
            </div>
            <div class="col-12  mt-3">
                     <h5 class="card-title">Distance</h5>
                <p class="card-text">{{ $eventMilestone->distance }}</p>
               
            </div>
            <div class="col-12 mt-3">
                <h5 class="card-title">Description</h5>
                <p class="card-text">{{ $eventMilestone->description }}</p>
            </div>

            <div class="col-12 mt-3">
                <h5 class="card-title">Data</h5>
                <p class="card-text">{{ $eventMilestone->video_url }}</p>
            </div>
            <div class="col-6 mt-3">
                <h5>Logo</h5>
                <p class="card-text">
                <div class="mx-auto my-6">
                    <img src="{{ $eventMilestone->logo }}" alt="Avatar Image" class="img-fluid">
                </div>
                </p>
            </div>
            <div class="col-6 mt-3">
                <h5>Team Logo</h5>
                <p class="card-text">
                <div class="mx-auto my-6">
                    <img src="{{ $eventMilestone->team_logo }}" alt="Avatar Image"
                        class="img-fluid">
                </div>
                </p>
            </div>
        </div>
    </div>