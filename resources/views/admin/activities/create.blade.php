<x-admin-layout>

    <div class="row g-6">
        <div class="col-md-12">
            <div class="card card-action">
                <div class="card-header">
                    <h5 class="card-action-title mb-0">{{ $activity?'Update':'Add'}} Activity</h5>
                    <div class="card-action-element">
                        <ul class="list-inline mb-0">
                            <li class="list-inline-item">
                                <a href="{{ route('admin.events.activities', $eventId) }}"
                                   class="btn btn-label-primary">Back to List</a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <x-alert.validation :errors=$errors></x-alert.validation>
                    <form action="" class="needs-validation" enctype="multipart/form-data" method="POST" id="event-form"
                          novalidate>
                        @csrf
                        <div class="row">
                            <div class="mb-4 col-xl-4 col-sm-12 col-md-6">
                                <label for="sponsor" class="form-label">Sponsor</label>
                                <input type="text" id="sponsor" name="sponsor" class="form-control"
                                       value="{{ $activity->sponsor ?? old('sponsor') }}" required>
                            </div>
                            <div class="mb-4 col-xl-4 col-sm-12 col-md-6">
                                <label for="category" class="form-label">Category</label>
                                <input type="text" id="category" name="category" class="form-control"
                                       value="{{ $activity->category ?? old('category') }}" required>
                            </div>
                            <div class="mb-4 col-xl-4 col-sm-12 col-md-6">
                                <label for="group" class="form-label">Group</label>
                                <input type="text" id="group" name="group" class="form-control"
                                       value="{{ $activity->group ?? old('group') }}" required>
                            </div>
                            <div class="mb-4 col-xl-4 col-sm-12 col-md-6">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" id="name" name="name"
                                       class="form-control @error('name') parsley-error @enderror"
                                       value="{{ $activity->name ?? old('name') }}"
                                       data-parsley-trigger="change" required>
                            </div>

                            <div class="mb-4 col-xl-4 col-sm-12 col-md-6">
                                <label for="total_points" class="form-label">Total Points</label>
                                <input type="number" name="total_points" id="total_points"
                                       class="form-control @error('total_points') parsley-error @enderror"
                                       value="{{ $activity->total_points ?? old('total_points') }}" required
                                       data-parsley-trigger="change">
                            </div>
                            <div class="mb-4 col-xl-4 col-sm-12 col-md-6">
                                <label for="sports" class="form-label">Sports</label>
                                <select class="form-select select2 @error('sports') parsley-error @enderror"
                                        name="sports[]" id="sports" multiple
                                        aria-label="Default select example" data-parsley-trigger="change"
                                        aria-label="Default select example" required>
                                    @foreach(['RUNNING','WALKING', 'BIKING', 'SWIMMING', 'OTHER'] as $sportType)
                                        <option
                                            value="{{ $sportType }}" {{ in_array($sportType, old('sports',$sports))?'selected':'' }}>{{ $sportType }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-4 col-xl-4 col-sm-12 col-md-6">
                                <label for="available_from" class="form-label">Available From</label>
                                <input type="text" name="available_from" id="available_from"
                                       class="form-control start-date @error('available_from') parsley-error @enderror"
                                       value="{{ $activity->available_from??old('available_from') }}" required
                                       data-parsley-trigger="change" placeholder="YYYY-MM-DD">
                            </div>
                            <div class="mb-4 col-xl-4 col-sm-12 col-md-6">
                                <label for="available_until" class="form-label">Available Until</label>
                                <input type="text" name="available_until" id="available_until"
                                       class="form-control end-date @error('available_until') parsley-error @enderror"
                                       required
                                       data-parsley-trigger="change" placeholder="YYYY-MM-DD"
                                       value="{{ $activity->available_until??old('available_until') }}">
                            </div>
                            <div class="mb-4 col-xl-12 col-sm-12 col-md-12">
                                <label for="tags" class="form-label">Tags</label>
                                <input type="text" name="tags" id="tags"
                                       class="form-control @error('tags') parsley-error @enderror"
                                       value="{{ $activity->tags ?? old('tags') }}"
                                       placeholder="WACKY,FUN,FITLIFE,HAPPY" required
                                       data-parsley-trigger="change">
                            </div>
                            <div class="mb-4 col-xl-12 col-sm-12 col-md-12">
                                <label for="social_hashtags" class="form-label">Social Hashtags</label>
                                <input type="text" name="social_hashtags" id="social_hashtags"
                                       class="form-control @error('social_hashtags') parsley-error @enderror"
                                       value="{{ $activity->social_hashtags ?? old('social_hashtags') }}"
                                       placeholder="#FITLIFEPROJECT #IMPACT #REWARDS #IMPACT8K" required
                                       data-parsley-trigger="change">
                            </div>
                            <div class="mb-4 col-xl-12 col-sm-12 col-md-12">
                                <label for="prize_url" class="form-label">Prize URL</label>
                                <input type="text" id="prize_url" name="prize_url" class="form-control"
                                       value="{{ $activity->prize_url??'' }}" placeholder="">
                            </div>
                            <div class="mb-4 col-xl-12 col-sm-12 col-md-12">
                                <label for="prize_description" class="form-label">Prize Description</label>
                                <textarea class="form-control @error('prize_description') parsley-error @enderror"
                                          name="prize_description" id="prize_description" data-parsley-trigger="change"
                                          rows="3"
                                          required>{{ $activity->prize_description??'' }}</textarea>
                            </div>
                            <div class="mb-4 col-xl-12 col-sm-12 col-md-12">
                                <label for="description" class="form-label">Description</label>
                                <div class="w-100 text-editor" id="description-editor"
                                     data-textarea-el="#description"></div>
                                <textarea name="description" id="description"
                                          class="form-control d-none">{{ $activity->description ?? old('description') }}</textarea>
                            </div>
                        </div>
                        <div class="mb-4 col-xl-12 col-sm-12 col-md-12">
                            <label for="about_title" class="form-label">About Title</label>
                            <input type="text" id="about_title" name="about_title"
                                   class="form-control @error('about_title') parsley-error @enderror"
                                   value="{{ $activity->about_title ?? old('about_title') }}"
                                   data-parsley-trigger="change" required>
                        </div>
                        <div class="mb-4 col-xl-12 col-sm-12 col-md-12">
                            <label for="about_description" class="form-label">About Description</label>
                            <div class="w-100 text-editor" id="about-description-editor"
                                 data-textarea-el="#about_description"></div>
                            <textarea name="about_description" id="about-description"
                                      class="form-control d-none">{{ $activity->about_description ?? old('about_description') }}</textarea>
                        </div>

                        <div class="d-flex justify-content-between mt-3">
                            <button type="submit" class="btn btn-primary">{{ $activity?'Update':'Add'}} Activity
                            </button>
                            @if($activity)
                                <a href="{{ route('admin.events.activity.milestones', [$activity->event_id, $activity->id]) }}"
                                   class="btn btn-dark">Milestones</a>
                            @endif
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
        <link rel="stylesheet" href="{{ asset('assets/vendor/libs/quill/editor.css') }}" />
    @endpush
    @push('scripts')
        {{-- <script src="{{ asset('assets/js/plugins/parsley.min.js') }}"></script>--}}
        <script src="{{ asset('assets/vendor/libs/@form-validation/popular.js') }}"></script>
        <script src="{{ asset('assets/vendor/libs/@form-validation/bootstrap5.js') }}"></script>
        <script src="{{ asset('assets/vendor/libs/@form-validation/auto-focus.js') }}"></script>
        <script src="{{ asset('assets/vendor/libs/moment/moment.js') }}"></script>
        <script src="{{ asset('assets/vendor/libs/flatpickr/flatpickr.js') }}"></script>
        <script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
        <script src="{{ asset('assets/vendor/libs/quill/quill.js') }}"></script>
        <script type="text/javascript">
            (function() {
                'use strict';
                $('.select2').select2();

                const descriptionEditor = new Quill('#description-editor', {
                    bounds: '#description-editor',
                    placeholder: 'Type Something...',

                    theme: 'snow'
                });

                descriptionEditor.on('text-change', function(delta, oldDelta, source) {
                    $('#description').val(descriptionEditor.container.firstChild.innerHTML);
                });

                descriptionEditor.setContents(descriptionEditor.clipboard.convert({ html: $('#description').val() }), 'silent');

                const aboutDescriptionEditor = new Quill('#about-description-editor', {
                    bounds: '#about-description-editor',
                    placeholder: 'Type Something...',

                    theme: 'snow'
                });

                aboutDescriptionEditor.on('text-change', function(delta, oldDelta, source) {
                    $('#about-description').val(aboutDescriptionEditor.container.firstChild.innerHTML);
                });

                aboutDescriptionEditor.setContents(aboutDescriptionEditor.clipboard.convert({ html: $('#about-description').val() }), 'silent');

                $('#available_from').flatpickr({
                    monthSelectorType: 'static',
                    static: true,
                    onChange: function(sel_date, date_str) {
                        flatpickrEndDate.set('minDate', date_str);
                    }
                });

                const flatpickrEndDate = $('#available_until').flatpickr({
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
