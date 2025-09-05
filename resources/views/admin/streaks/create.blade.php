<x-admin-layout>

    <div class="row g-6">

        <div class="col-md-12">
            <div class="card">
                <h5 class="card-header">{{ $event->name }}: {{ $eventStreak? 'Update' : 'Add' }} Streak</h5>
                <div class="card-body">
                    <x-alert.validation :errors=$errors></x-alert.validation>

                    <form action="" class="needs-validation" enctype="multipart/form-data" method="POST" id="event-form"
                          novalidate>
                        @csrf
                        <div class="row">
                            <div class="mb-4 col-xl-4 col-sm-12 col-md-6">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" id="name" name="name"
                                       class="form-control @error('name') parsley-error @enderror"
                                       value="{{ $eventStreak->name ?? old('name') }}"
                                       data-parsley-trigger="change" required>
                            </div>
                            <div class="mb-4 col-xl-4 col-sm-12 col-md-6">
                                <label for="days_count" class="form-label">Days Count</label>
                                <input type="text" name="days_count" id="days_count"
                                       class="form-control @error('days_count') parsley-error @enderror"
                                       value="{{ $eventStreak->days_count ?? old('days_count') }}" required
                                       data-parsley-trigger="change">
                            </div>

                            <div class="mb-4 col-xl-4 col-sm-12 col-md-6">
                                <label for="min_distance" class="form-label">Mininum Distance (Miles)</label>
                                <input type="number" name="min_distance" id="min_distance"
                                       class="form-control @error('min_distance') parsley-error @enderror"
                                       value="{{ $eventStreak->min_distance ?? old('min_distance') }}" required
                                       data-parsley-trigger="change">
                            </div>
                            <div class="mb-4 col-xl-4 col-sm-12 col-md-6">
                                <label for="email_template_id" class="form-label">Email Template</label>
                                <select class="form-select" name="email_template_id" id="email_template_id"
                                        aria-label="Default select example" data-parsley-trigger="change"
                                        aria-label="Default select example">
                                    <option selected="" value="">Select Email Template</option>
                                    @foreach($emailTemplates as $emailTemplate)
                                        <option
                                            value="{{ $emailTemplate->id }}" {{ (old('email_template_id',$selectedEmailTemplate) == $emailTemplate->id)?'selected':''}}>{{ $emailTemplate->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-4 col-xl-4 col-sm-12 col-md-6">
                                <label for="logo" class="form-label">Logo</label>
                                <input type="file" id="logo" name="logo" data-preview=".logo-preview"
                                       class="form-control choose-file">
                                <div class="logo-preview {{ isset($eventStreak->logo) && $eventStreak->logo?'':'d-none' }} mt-3">
                                    <img src="{{ $eventStreak->logo??'' }}" alt="" class="img-fluid img" style="height: 100px;" />
                                </div>
                            </div>

                            <div class="mb-4 col-xl-4 col-sm-12 col-md-6">
                                <label for="calendar_logo" class="form-label">Calendar Logo</label>
                                <input type="file" id="calendar_logo" data-preview=".calendar-logo-preview"
                                       name="calendar_logo" class="form-control choose-file">
                                <div class="calendar-logo-preview {{ isset($eventStreak->calendar_logo) && $eventStreak->calendar_logo?'':'d-none' }} mt-3">
                                    <img src="{{ $eventStreak->calendar_logo??'' }}" class="img-fluid img" style="height: 100px;" />
                                </div>
                            </div>
                            <div class="mb-4 col-xl-4 col-sm-12 col-md-6">
                                <label for="bib-image" class="form-label">Bibs Image</label>
                                <input type="file" id="bib-image" data-preview=".bib-image-preview"
                                       name="bib_image" class="form-control choose-file">
                                <div class="bib-image-preview {{ isset($eventStreak->bib_image) && $eventStreak->bib_image?'':'d-none' }} mt-3">
                                    <img src="{{ $eventStreak->bib_image??'' }}" class="img-fluid img" style="height: 100px;" />
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-3">

                            <button type="submit" class="btn btn-primary">{{ $eventStreak? 'Update' : 'Add' }}
                                Streak
                            </button>

                            <a href="{{ route('admin.events.streaks',$event->id) }}" class="btn btn-label-primary">Back
                                to Streaks</a>
                        </div>

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
        <link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
    @endpush
    @push('scripts')
        {{-- <script src="{{ asset('assets/js/plugins/parsley.min.js') }}"></script>--}}
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

                function readURL(input) {
                    console.log(input.files, input.files[0]);
                    if (input.files && input.files[0]) {
                        console.log(input);
                        var reader = new FileReader();
                        reader.onload = function(e) {
                            const previewEl = $($(input).data('preview'));
                            console.log(previewEl);
                            previewEl.removeClass('d-none');
                            previewEl.find('img').attr('src', e.target.result);
                        };
                        reader.readAsDataURL(input.files[0]);
                    }
                }

                $('.choose-file').change(function(e) {

                    readURL(this);
                });

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
