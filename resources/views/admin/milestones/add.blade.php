<div class="card-body">
    <form action="" class="needs-validation" enctype="multipart/form-data" method="POST" id="event-form" novalidate>
        @csrf

        <div class="mb-4">
            <label for="name" class="form-label">Name</label>
            <input type="text" id="name" name="name"
                   class="form-control @error('name') parsley-error @enderror" value="{{ $eventMilestone->name ?? '' }}"
                   data-parsley-trigger="change" required>
        </div>
        <div class="mb-4">
            <label for="description" class="form-label">Description</label>
            <textarea name="description" id="description"
                      class="form-control">{{ $eventMilestone->description ?? '' }}</textarea>
        </div>
        <div class="mb-4">
            <label for="distance" class="form-label">Distance (Miles)</label>
            <input type="number" name="distance" id="distance"
                   class="form-control @error('distance') parsley-error @enderror"
                   value="{{ $eventMilestone->distance ?? '' }}" required
                   data-parsley-trigger="change">
        </div>
        <div class="mb-4">
            <label for="video_url" class="form-label">Video URL</label>
            <input type="text" name="video_url" id="video_url"
                   class="form-control @error('video_url') parsley-error @enderror"
                   value="{{ $eventMilestone->video_url ?? '' }}" required
                   data-parsley-trigger="change">
        </div>
        <div class="mb-4">
            <label for="logo" class="form-label">Logo</label>
            <input type="file" id="logo" name="logo" class="form-control">
        </div>
        <div class="mb-4">
            <label for="team_logo" class="form-label">Team Logo</label>
            <input type="file" id="team_logo" name="team_logo" class="form-control">
        </div>

        <button type="submit" class="btn btn-primary">{{ $eventMilestone? 'Update' : 'Add' }} Milestone</button>

    </form>
</div>
