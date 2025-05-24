<x-admin-layout>

    <div class="row g-6">

        <div class="col-md-8 offset-2">
            <div class="card">
                <h5 class="card-header">Add Event</h5>
                <div class="card-body">
                    <form action="" class="needs-validation" method="POST" id="event-form" novalidate>
                        @csrf
                        <div class="mb-4">

                            <label for="event_type" class="form-label">Event Type</label>
                            <select class="form-select" name="event_type" id="event_type"
                                    aria-label="Default select example" data-parsley-trigger="change"
                                    aria-label="Default select example" required>
                                <option selected="" value="">Select Event Type</option>
                                @foreach($eventTypes as $eventTypeKey => $eventType)
                                    <option value="{{ $eventTypeKey }}">{{ $eventType }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-4">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" id="name" name="name"
                                   class="form-control @error('name') parsley-error @enderror"
                                   data-parsley-trigger="change" required>
                        </div>
                        <div class="mb-4">
                            <label for="social_hashtags" class="form-label">Social Hashtags</label>
                            <input type="text" name="social_hashtags" id="social_hashtags" class="form-control">
                        </div>
                        <div class="mb-4">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') parsley-error @enderror"
                                      name="description" id="description" data-parsley-trigger="change" rows="3"
                                      required></textarea>
                        </div>
                        <div class="mb-4">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" name="start_date" id="start_date"
                                   class="form-control @error('start_date') parsley-error @enderror" required
                                   data-parsley-trigger="change" placeholder="YYYY-MM-DD">
                        </div>
                        <div class="mb-4">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" name="end_date" id="end_date"
                                   class="form-control @error('end_date') parsley-error @enderror" required
                                   data-parsley-trigger="change" placeholder="YYYY-MM-DD">
                        </div>
                        <div class="mb-4">
                            <label for="total_points" class="form-label">Total Points</label>
                            <input type="text" id="total_points" name="total_points" class="form-control">
                        </div>
                        <div class="mb-4">
                            <label for="registration_url" class="form-label">Registration URL</label>
                            <input type="text" id="registration_url" name="registration_url" class="form-control">
                        </div>
                        <div class="mb-4">
                            <label for="modalities" class="form-label">Modalities</label>
                            <select class="form-select select2" name="modalities" id="modalities"
                                    aria-label="Default select example" data-parsley-trigger="change" multiple
                                     required>
                                @foreach($modalities as $modality)
                                    <option value="{{ $modality->name }}">{{ $modality->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-4">
                            <label for="goals" class="form-label">Goals</label>
                            <input type="text" id="goals" name="goals" class="form-control" placeholder="500,1000,1500,2000">
                        </div>
                        <button type="submit" class="btn btn-primary">Add Event</button>

                    </form>
                </div>
            </div>
        </div>
    </div>
    @push('stylesheets')
        <link rel="stylesheet" href="{{ asset('assets/vendor/libs/@form-validation/form-validation.css') }}">
        <link rel="stylesheet" href="{{ asset('assets/vendor/libs/flatpickr/flatpickr.css') }}" />
        <link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
    @endpush
    @push('scripts')
        {{--        <script src="{{ asset('assets/js/plugins/parsley.min.js') }}"></script>--}}
        <script src="{{ asset('assets/vendor/libs/@form-validation/popular.js') }}"></script>
        <script src="{{ asset('assets/vendor/libs/@form-validation/bootstrap5.js') }}"></script>
        <script src="{{ asset('assets/vendor/libs/@form-validation/auto-focus.js') }}"></script>
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
                        flatpickrEndDate.set("minDate", date_str);
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
