<x-admin-layout>

    <div class="row g-6">

        <div class="col-md-12">
            <div class="card">
                <h5 class="card-header">{{ $event->name }}: {{ $eventMilestone? 'Update' : 'Add' }} Milestone</h5>
                <div class="card-body">
                    <x-alert.validation :errors=$errors></x-alert.validation>

                    <form action="" class="needs-validation" enctype="multipart/form-data" method="POST" id="milestone-form"
                          novalidate>
                        @csrf

                        <div class="row">
                            <div class="mb-4 col-xl-4 col-sm-12 col-md-6">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" id="name" name="name"
                                       class="form-control @error('name') parsley-error @enderror"
                                       value="{{ $eventMilestone->name ??old('name') }}"
                                       data-parsley-trigger="change" required>
                            </div>

                            <div class="mb-4 col-xl-4 col-sm-12 col-md-6">
                                <label for="distance" class="form-label">Distance (Miles)</label>
                                <input type="number" name="distance" id="distance"
                                       class="form-control @error('distance') parsley-error @enderror"
                                       value="{{ $eventMilestone->distance ?? old('distance') }}" required
                                       data-parsley-trigger="change" min="1">
                            </div>
                            <div class="mb-4 col-xl-4 col-sm-12 col-md-6">
                                <label for="video_url" class="form-label">Video URL</label>
                                <input type="text" name="video_url" id="video_url"
                                       class="form-control @error('video_url') parsley-error @enderror"
                                       value="{{ $eventMilestone->video_url ?? old('video_url') }}"
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
                            @if($isRegularEvent)
                                <div class="mb-4 col-xl-8 col-sm-12 col-md-6">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea name="description" id="description"
                                              class="form-control">{{ $eventMilestone->description ?? old('description') }}</textarea>
                                </div>
                            @endif
                            <div class="mb-4 col-xl-4 col-sm-12 col-md-6">
                                <label for="logo" class="form-label">Logo</label>
                                <input type="file" id="logo" name="logo" data-preview=".logo-preview"
                                       class="form-control choose-file" {{ isset($eventMilestone->logo) && $eventMilestone->logo?'':'required' }}>
                                <div class="logo-preview {{ isset($eventMilestone->logo) && $eventMilestone->logo?'':'d-none' }} mt-3">
                                    <img src="{{ $eventMilestone->logo??'' }}" class="img-fluid img" style="height: 100px;" />
                                </div>
                            </div>
                            <div class="mb-4 col-xl-4 col-sm-12 col-md-6">
                                <label for="calendar_logo" class="form-label">Calendar Logo</label>
                                <input type="file" id="calendar_logo" data-preview=".calendar-logo-preview"
                                       name="calendar_logo" class="form-control choose-file" {{ isset($eventMilestone->calendar_logo) && $eventMilestone->calendar_logo?'':'required' }}>
                                <div class="calendar-logo-preview {{ isset($eventMilestone->calendar_logo) && $eventMilestone->calendar_logo?'':'d-none' }} mt-3">
                                    <img src="{{ $eventMilestone->calendar_logo??'' }}" class="img-fluid img" style="height: 100px;" />
                                </div>
                            </div>
                            @if($isRegularEvent)
                                <div class="mb-4 col-xl-4 col-sm-12 col-md-6">
                                    <label for="team_logo" class="form-label">Team Logo</label>
                                    <input type="file" id="team_logo" data-preview=".team-logo-preview"
                                           name="team_logo" class="form-control choose-file" {{ isset($eventMilestone->team_logo) && $eventMilestone->team_logo?'':'required' }}>
                                    <div class="team-logo-preview {{ isset($eventMilestone->team_logo) && $eventMilestone->team_logo?'':'d-none' }} mt-3">
                                        <img src="{{ $eventMilestone->team_logo??'' }}" class="img-fluid img" style="height: 100px;" />
                                    </div>
                                </div>
                                <div class="mb-4 col-xl-4 col-sm-12 col-md-6">
                                    <label for="calendar_team_logo" class="form-label">Calendar Team Logo</label>
                                    <input type="file" id="calendar_team_logo" data-preview=".calendar-team-logo-preview"
                                           name="calendar_team_logo" class="form-control choose-file" {{ isset($eventMilestone->calendar_team_logo) && $eventMilestone->calendar_team_logo?'':'required' }}>
                                    <div class="calendar-team-logo-preview {{ isset($eventMilestone->calendar_team_logo) && $eventMilestone->calendar_team_logo?'':'d-none' }} mt-3">
                                        <img src="{{ $eventMilestone->calendar_team_logo??'' }}" class="img-fluid img" style="height: 100px;" />
                                    </div>
                                </div>
                            @else
                                <div class="mb-4 col-xl-4 col-sm-12 col-md-6">
                                    <label for="logo_bw" class="form-label">BW Logo</label>
                                    <input type="file" id="logo_bw" name="bw_logo" data-preview=".logo_bw-preview"
                                           class="form-control choose-file" {{ isset($eventMilestone->bw_logo) && $eventMilestone->bw_logo?'':'required' }}>
                                    <div class="logo_bw-preview {{ isset($eventMilestone->bw_logo) && $eventMilestone->bw_logo?'':'d-none' }} mt-3">
                                        <img src="{{ $eventMilestone->bw_logo??'' }}" class="img-fluid img" style="height: 100px;" />
                                    </div>
                                </div>
                                <div class="mb-4 col-xl-4 col-sm-12 col-md-6">
                                    <label for="calendar_bw_logo" class="form-label">BW Calendar Logo</label>
                                    <input type="file" id="bw_calendar_logo" data-preview=".calendar_bw-logo-preview"
                                           name="bw_calendar_logo" class="form-control choose-file" {{ isset($eventMilestone->bw_calendar_logo) && $eventMilestone->bw_calendar_logo?'':'required' }}>
                                    <div class="calendar_bw-logo-preview {{ isset($eventMilestone->bw_calendar_logo) && $eventMilestone->bw_calendar_logo?'':'d-none' }} mt-3">
                                        <img src="{{ $eventMilestone->bw_calendar_logo??'' }}" class="img-fluid img" style="height: 100px;" />
                                    </div>
                                </div>
                            @endif
                            <div class="mb-4 col-xl-4 col-sm-12 col-md-6">
                                <label for="bib-image" class="form-label">Bibs Image</label>
                                <input type="file" id="bib-image" data-preview=".bib-image-preview"
                                       name="bib_image" class="form-control choose-file" {{ isset($eventMilestone->bib_image) && $eventMilestone->bib_image?'':'required' }}>
                                <div class="bib-image-preview {{ isset($eventMilestone->bib_image) && $eventMilestone->bib_image?'':'d-none' }} mt-3">
                                    <img src="{{ $eventMilestone->bib_image??'' }}" class="img-fluid img" style="height: 100px;" />
                                </div>
                            </div>
                            @if($isRegularEvent)
                            <div class="mb-4 col-xl-4 col-sm-12 col-md-6">
                                <label for="team-bib-image" class="form-label">Team Bibs Image</label>
                                <input type="file" id="team-bib-image" data-preview=".team-bib-image-preview"
                                       name="team_bib_image" class="form-control choose-file" {{ isset($eventMilestone->team_bib_image) && $eventMilestone->team_bib_image?'':'required' }}>
                                <div class="team-bib-image-preview {{ isset($eventMilestone->team_bib_image) && $eventMilestone->team_bib_image?'':'d-none' }} mt-3">
                                    <img src="{{ $eventMilestone->team_bib_image??'' }}" class="img-fluid img" style="height: 100px;" />
                                </div>
                            </div>
                                @endif
                        </div>
                        <div class="d-flex justify-content-between mt-3">

                            <button type="submit" class="btn btn-primary">{{ $eventMilestone? 'Update' : 'Add' }}
                                Milestone
                            </button>

                            <a href="{{ $backUrl }}" class="btn btn-label-primary">Back
                                to Milestones</a>
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
