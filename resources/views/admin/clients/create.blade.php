<x-admin-layout>
    <div class="row g-6">

        <div class="col-md-12">
            <div class="card">
                <h5 class="card-header">{{ $client?'Update':'Add'}} Client Details</h5>
                <div class="card-body">
                    <x-alert.validation :errors=$errors></x-alert.validation>
                    <form action="" class="needs-validation" method="POST" id="event-form" autocomplete="nope" enctype="multipart/form-data"
                          novalidate>
                        @csrf
                        <div class="row">
                            <div class="mb-4 col-xl-6 col-sm-12">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" id="name" name="name"
                                       value="{{ $client->name??old('name') }}"
                                       class="form-control @error('name') parsley-error @enderror"
                                       data-parsley-trigger="change" required>
                            </div>
                            <div class="mb-4 col-xl-6 col-sm-12">
                                <label for="address" class="form-label">Address</label>
                                <textarea id="address" name="address"
                                       class="form-control @error('address') parsley-error @enderror"
                                          data-parsley-trigger="change" required>{{ $client->address??old('address') }}</textarea>
                            </div>
                        </div>
                        <div class="mb-4 col-xl-4 col-sm-12 col-md-6">
                            <label for="logo" class="form-label">Logo</label>
                            <input type="file" id="logo" name="logo" data-preview=".logo-preview"
                                   class="form-control choose-file">

                        </div>
                        <div class="mb-4 col-xl-4 col-sm-12 col-md-6">
                            <div
                                class="logo-preview {{ (($client && !$client->logo_url) || !$client)?'d-none':'' }} mt-3">
                                <img src="{{ $client->logo_url??'' }}" class="img-fluid img"
                                     style="height: 100px;" onerror="this.src='{{ url('/images/default-placeholder.png') }}'"/>
                            </div>
                        </div>
                        <div class="mb-4 col-xl-4 col-sm-12 col-md-6">
                            <label for="is_active" class="form-label">Status</label>
                            <select class="form-select select2" name="is_active" id="is_active"
                                    aria-label="Default select example" data-parsley-trigger="change">
                                @foreach([1 => 'Active', 0 => 'Inactive'] as $statusKey => $status)
                                    <option
                                        value="{{ $statusKey }}" {{ (($client && $statusKey == $client->is_active) || old('status', 1) == $statusKey)?'selected':''}}>{{ $status }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-4">
                            <h5 class="m-0 me-2">Assign Events</h5>
                            @if($client)
                                <div class="form-check mt-4">
                                    <input class="form-check-input show-assigned" type="checkbox" value="1"
                                           id="show-assigned" checked>
                                    <label class="form-check-label" for="show-assigned"> Show Assigned Only </label>
                                </div>
                            @endif
                            <div class="invalid-feedback w-100" id="checkbox-feedback">
                                Please select at least one option.
                            </div>
                            <div class="table-responsive overflow-hidden" style="height: 300px" id="table-scrollable">
                                <table class="table card-table">
                                    <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Event Start Date</th>
                                        <th>Event End Date</th>
                                    </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0">
                                    @foreach($events as $event)
                                        <tr class="{{ $event->hasClientParticipation($client)?'user-assigned':'user-unassigned' }}">
                                            <td class="w-50 ps-0 pt-0">
                                                <div class="d-flex justify-content-start align-items-center">
                                                    <div class="form-check mt-4">
                                                        <input class="form-check-input" type="checkbox" name="event[]"
                                                               value="{{ $event->id }}" id="event-item-{{ $event->id }}"
                                                               {{ $event->hasClientParticipation($client) || in_array($event->id, old('event',[]))?'checked':'' }} data-end-item="subscription-item-{{$event->id}}" {{ $event->isPastEvent()?'disabled':'' }}>
                                                        <label class="form-check-label"
                                                               for="event-item-{{ $event->id }}"> {{ $event->name }}</label>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-start pe-0 text-nowrap">
                                                {{ \Carbon\Carbon::parse($event->start_date)->format('m/d/Y') }}
                                            </td>
                                            <td class="text-start pe-0 text-nowrap">
                                                {{ \Carbon\Carbon::parse($event->end_date)->format('m/d/Y') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between mt-3">
                            <button type="submit" class="btn btn-primary">{{ $client?'Update':'Add'}} Client</button>
                            <a href="{{ route('admin.clients') }}" class="btn btn-label-primary">Back to List</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @push('stylesheets')
        <link rel="stylesheet" href="{{ asset('assets/vendor/libs/@form-validation/form-validation.css') }}">
        <link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
        <link rel="stylesheet" href="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />
        <link rel="stylesheet" href="{{ asset('assets/vendor/libs/flatpickr/flatpickr.css') }}" />
    @endpush
    @push('scripts')
        {{-- <script src="{{ asset('assets/js/plugins/parsley.min.js') }}"></script>--}}
        <script src="{{ asset('assets/vendor/libs/@form-validation/popular.js') }}"></script>
        <script src="{{ asset('assets/vendor/libs/@form-validation/bootstrap5.js') }}"></script>
        <script src="{{ asset('assets/vendor/libs/@form-validation/auto-focus.js') }}"></script>
        <script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
        <script src="{{ asset('assets/vendor/libs/moment/moment.js') }}"></script>
        <script src="{{ asset('assets/vendor/libs/flatpickr/flatpickr.js') }}"></script>
        <script src="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
        <script type="text/javascript">
            (function() {
                'use strict';

                function readURL(input) {
                    if (input.files && input.files[0]) {
                        console.log(input);
                        var reader = new FileReader();
                        reader.onload = function(e) {
                            const previewEl = $($(input).data('preview'));
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

                new PerfectScrollbar('#table-scrollable', {
                    wheelPropagation: false
                });

                const flatpickrEndDate = {};

                $('.start-date').flatpickr({
                    monthSelectorType: 'static',
                    static: true,
                    onChange: function(sel_date, date_str, e) {
                        let dataItem = e.element.data('end-item');
                        console.log(dataItem, $(e.element));
                        flatpickrEndDate[dataItem].set('minDate', date_str);
                    }
                });
                $('.end-date').each(function() {
                    let dataItem = $(this).attr('data-item');
                    flatpickrEndDate[dataItem] = $(this).flatpickr({
                        monthSelectorType: 'static',
                        static: true
                    });
                });

                if ($('.show-assigned').length) {
                    $('.show-assigned').change(function() {
                        let userUnassignedItems = $('.user-unassigned');
                        if ($(this).is(':checked')) {
                            userUnassignedItems.addClass('d-none');
                        } else {
                            userUnassignedItems.removeClass('d-none');
                        }
                    }).trigger('change');
                }

                let validateEventSelection = function() {
                    const checkboxes = $('input[name="event[]"]');
                    const checkboxFeedback = $('#checkbox-feedback');
                    const checkedItem = $('input[name="event[]"]:checked');

                    checkboxes.each(function() {
                        if (checkedItem.length === 0) {
                            $(this).removeClass('is-valid');
                            $(this).addClass('is-invalid');
                            $(this)[0].setCustomValidity('Invalid');
                        } else {
                            $(this).removeClass('is-invalid');
                            $(this).addClass('is-valid');
                            $(this)[0].setCustomValidity('');
                        }
                    });
                };

                $('input[name="event[]"]').change(function(){
                    if($('#event-form').hasClass('was-validated')){
                        validateEventSelection();
                    }
                })

                // Fetch all the forms we want to apply custom Bootstrap validation styles to
                var forms = document.querySelectorAll('.needs-validation');

                // Loop over them and prevent submission
                Array.prototype.slice.call(forms)
                    .forEach(function(form) {

                        form.addEventListener('submit', function(event) {

                            const checkedItem = $('input[name="event[]"]:checked');
                            validateEventSelection();
                            if (!form.checkValidity() || checkedItem.length === 0) {
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
