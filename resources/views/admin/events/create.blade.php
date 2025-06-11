<x-admin-layout>

    <div class="row g-6">

        <div class="col-md-12">
            <div class="card">
                <h5 class="card-header">{{ $event?'Update':'Add'}} Event</h5>
                <div class="card-body">
                    <x-alert.validation :errors=$errors></x-alert.validation>
                    <form action="" class="needs-validation" method="POST" id="event-form" novalidate>
                        @csrf
                        <div class="row">
                            <div class="mb-4 col-xl-4 col-sm-12 col-md-6">

                                <label for="event_type" class="form-label">Event Type</label>
                                <select class="form-select" name="event_type" id="event_type"
                                        aria-label="Default select example" data-parsley-trigger="change"
                                        aria-label="Default select example" required>
                                    <option selected="" value="">Select Event Type</option>
                                    @foreach($eventTypes as $eventTypeKey => $eventType)
                                        <option
                                            value="{{ $eventTypeKey }}" {{ ($event && $event->event_type == $eventTypeKey)?'selected':''}}>{{ $eventType }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-4 col-xl-4 col-sm-12 col-md-6">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" id="name" name="name" value="{{ $event->name??'' }}"
                                       class="form-control @error('name') parsley-error @enderror"
                                       data-parsley-trigger="change" required>
                            </div>
                            <div class="mb-4 col-xl-4 col-sm-12 col-md-6">
                                <label for="social_hashtags" class="form-label">Social Hashtags</label>
                                <input type="text" name="social_hashtags" id="social_hashtags"
                                       value="{{ $event->social_hashtags??'' }}" class="form-control">
                            </div>
                            <div class="mb-4 col-xl-4 col-sm-12 col-md-6">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" name="start_date" id="start_date"
                                       class="form-control @error('start_date') parsley-error @enderror"
                                       value="{{ $event->start_date??'' }}" required
                                       data-parsley-trigger="change" placeholder="YYYY-MM-DD">
                            </div>
                            <div class="mb-4 col-xl-4 col-sm-12 col-md-6">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" name="end_date" id="end_date"
                                       class="form-control @error('end_date') parsley-error @enderror" required
                                       data-parsley-trigger="change" placeholder="YYYY-MM-DD"
                                       value="{{ $event->end_date??'' }}">
                            </div>
                            <div class="mb-4 col-xl-4 col-sm-12 col-md-6">
                                <label for="total_points" class="form-label">Total Points</label>
                                <input type="text" id="total_points" name="total_points" class="form-control"
                                       value="{{ $event->total_points??'' }}">
                            </div>

                            <div class="mb-4 col-xl-4 col-sm-12 col-md-6">
                                <label for="modalities" class="form-label">Modalities</label>
                                <select class="form-select select2" name="modalities[]" id="modalities"
                                        aria-label="Default select example" data-parsley-trigger="change" multiple
                                        required>
                                    @foreach($modalities as $modality)
                                        <option
                                            value="{{ $modality->name }}" {{ in_array($modality->name,$selectedModalities)?'selected':''}}>{{ $modality->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-4 col-xl-4 col-sm-12 col-md-6">
                                <label for="registration_url" class="form-label">Registration URL</label>
                                <input type="text" id="registration_url" name="registration_url" class="form-control"
                                       value="{{ isset($event->registration_url)?$event->registration_url == '#'?'':$event->registration_url:'' }}">
                            </div>

                            <div class="mb-4 col-xl-4 col-sm-12 col-md-6">
                                <label for="goals" class="form-label">Goals</label>
                                <input type="text" id="goals" name="goals" class="form-control"
                                       value="{{ $event && $event->goals?implode(',',json_decode($event->goals)):'' }}"
                                       placeholder="500,1000,1500,2000">
                            </div>

                            <div class="mb-4 col-xl-4 col-sm-12 col-md-6">
                                <label for="bibs_name" class="form-label">Bibs Name</label>
                                <input type="text" id="bibs_name" name="bibs_name" class="form-control"
                                       value="{{ $event->bibs_name??'' }}" placeholder="">
                            </div>
                            <div class="mb-4 col-xl-4 col-sm-12 col-md-6">
                                <label for="open_status" class="form-label">Event Status</label>
                                <select class="form-select select2" name="open_status" id="open_status"
                                        aria-label="Default select example" data-parsley-trigger="change">
                                    @foreach([null => 'Default',1 => 'Open',0 =>'Closed'] as $openStatusKey => $openStatus)
                                        <option
                                            value="{{ $openStatusKey }}" {{ $event && $openStatusKey == $event->open?'selected':''}}>{{ $openStatus }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-4 col-xl-4 col-sm-12 col-md-6">
                                <label for="event_group" class="form-label">Group</label>
                                <input type="text" id="event_group" name="event_group" class="form-control"
                                       value="{{ $event->event_group??'' }}" placeholder="">
                            </div>
                            <div class="mb-4 col-xl-4 col-sm-12 col-md-6">
                                <label for="logo" class="form-label">Logo</label>
                                <input type="file" id="logo" name="logo" data-preview=".logo-preview"
                                       class="form-control choose-file">
                                <div class="logo-preview {{ (($event && !$event->logo_url) || !$event)?'d-none':'' }} mt-3">
                                    <img src="{{ $event->logo_url??'' }}" class="img-fluid img" style="height: 100px;" />
                                </div>
                            </div>
                            <div class="mb-4 col-xl-8 col-sm-12 col-md-6">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control @error('description') parsley-error @enderror"
                                          name="description" id="description" data-parsley-trigger="change" rows="3"
                                          required>{{ $event->description??'' }}</textarea>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between mt-3">
                            <button type="submit" class="btn btn-primary">{{ $event?'Update':'Add'}} Event</button>
                            <a href="{{ route('admin.events') }}" class="btn btn-label-primary">Back to List</a>
                        </div>
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
        {{-- <script src="{{ asset('assets/js/plugins/parsley.min.js') }}"></script>--}}
        <script src="{{ asset('assets/vendor/libs/@form-validation/popular.js') }}"></script>
        <script src="{{ asset('assets/vendor/libs/@form-validation/bootstrap5.js') }}"></script>
        <script src="{{ asset('assets/vendor/libs/@form-validation/auto-focus.js') }}"></script>
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
