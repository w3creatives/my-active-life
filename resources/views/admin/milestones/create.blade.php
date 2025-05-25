<x-admin-layout>

    <div class="row g-6">

        <div class="col-md-8 offset-2">
            <div class="card">
                <h5 class="card-header">{{ $event->name }}: {{ $eventMilestone? 'Update' : 'Add' }} Milestone</h5>
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
                            <textarea name="description" id="description" class="form-control">{{ $eventMilestone->description ?? '' }}</textarea>
                        </div>
                        <div class="mb-4">
                            <label for="distance" class="form-label">Distance (Miles)</label>
                            <input type="number" name="distance" id="distance"
                                   class="form-control @error('distance') parsley-error @enderror" value="{{ $eventMilestone->distance ?? '' }}" required
                                   data-parsley-trigger="change">
                        </div>
                        <div class="mb-4">
                            <label for="video_url" class="form-label">Video URL</label>
                            <input type="text" name="video_url" id="video_url"
                                   class="form-control @error('video_url') parsley-error @enderror" value="{{ $eventMilestone->video_url ?? '' }}" required
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
            </div>
        </div>
    </div>
    @push('stylesheets')
        <link rel="stylesheet"
              href="{{ asset('assets/vendor/libs/@form-validation/form-validation.css') }}">
        <link rel="stylesheet"
              href="{{ asset('assets/vendor/libs/flatpickr/flatpickr.css') }}" />
        <link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}"
        />
    @endpush
    @push('scripts')
        {{--        <script src="{{ asset('assets/js/plugins/parsley.min.js') }}"></script>--}}
        <script src="{{ asset('assets/vendor/libs/@form-validation/popular.js') }}
                                    "></script>
        <script src="{{ asset('assets/vendor/libs/@form-validation/bootstrap5.js') }}
                                    "></script>
        <script src="{{ asset('assets/vendor/libs/@form-validation/auto-focus.js') }}
                                    "></script>
        <script src="{{ asset('assets/vendor/libs/moment/moment.js') }}"></script>
        <script src="{{ asset('assets/vendor/libs/flatpickr/flatpickr.js') }}"></script>
        <script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
        <script type="text/javascript">
            (function() {
                'use strict';
                $('.select2').select2();

                $('#start_date').flatpickr({
                    monthSelectorType: 'static',
                    static: true,
                    onChange: function(sel_date, date_str) {
                        flatpickrEndDate.set('minDate', date_str);
                    }
                });

                const flatpickrEndDate = $('#end_date').flatpickr({
                    monthSelectorType: 'static',
                    static: true
                });
                // Fetch all the forms we want to apply custom Bootstrap validation styles to
                var forms = document.querySelectorAll('.needs-validation');

                // Loop over them and prevent submission
                Array.prototype.slice.call(forms)
                    .forEach(function(form) {
                        form.addEventListener('submit', function(event) {
                            if (!form.checkValidity()) {
                                event.preventDefault();
                                event.stopPropagation();
                            }

                            form.classList.add('was-validated');
                        }, false);
                    });
            })();
        </script>
    @endpush
</x-admin-layout>
