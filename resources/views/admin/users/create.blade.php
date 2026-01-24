<x-admin-layout>
    <div class="row g-6">

        <div class="col-md-12">
            <div class="card">
                <h5 class="card-header">{{ $user?'Update':'Add'}} User Details</h5>
                <div class="card-body">
                    <x-alert.validation :errors=$errors></x-alert.validation>
                    <form action="" class="needs-validation" method="POST" id="event-form" autocomplete="nope"
                          novalidate>
                        <input type="email" style="display:none">
                        <input type="password" style="display:none">
                        @csrf
                        <div class="row">
                            <div class="mb-4 col-xl-4 col-sm-12">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" id="first_name" name="first_name"
                                       value="{{ $user->first_name??old('first_name') }}"
                                       class="form-control @error('first_name') parsley-error @enderror"
                                       data-parsley-trigger="change" required>
                            </div>
                            <div class="mb-4 col-xl-4 col-sm-12">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" id="last_name" name="last_name"
                                       value="{{ $user->last_name??old('last_name') }}"
                                       class="form-control @error('last_name') parsley-error @enderror"
                                       data-parsley-trigger="change" required>
                            </div>
                            <div class="mb-4 col-xl-4 col-sm-12">
                                <label for="client" class="form-label">Assign Client</label>
                                <select class="form-select select2-multiple" multiple="multiple" name="client[]" id="client"
                                        aria-label="Select Client" data-parsley-trigger="change" data-placeholder="Select Client" data-event-url="{{ route('admin.users.clients.events',$user->id??'') }}">
                                    @foreach($clients as $client)
                                        <option value="{{ $client->id }}" {{ $user && $user->hasClient($client)?'selected="selected"':''}}>{{ $client->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="mb-4 col-xl-6 col-sm-12">
                                <label for="display_name" class="form-label">Display Name</label>
                                <input type="text" name="display_name" id="display_name"
                                       value="{{ $user->display_name??old('display_name') }}" class="form-control"
                                       required>
                            </div>
                            <div class="mb-4 col-xl-6 col-sm-12">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" name="email" id="email"
                                       value="{{ $user->email??old('email') }}" class="form-control" autocomplete="nope"
                                       required>
                            </div>
                        </div>
                        <div class="mb-4">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input enable-password" type="checkbox"
                                       data-password-group=".password-group" name="enabled_password" id="add-password"
                                       value="1" {{ old('enabled_password')?'checked':'' }}>
                                <label class="form-check-label" for="add-password">Setup Password</label>
                            </div>
                        </div>
                        <div class="row">
                            <div class="mb-4 password-group col-xl-6 col-sm-12">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" name="password" id="password"
                                       value="{{ old('password') }}" class="form-control">
                            </div>
                            <div class="mb-4 password-group col-xl-6 col-sm-12">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" name="confirm_password" id="confirm_password"
                                       value="{{ old('confirm_password') }}" class="form-control">
                            </div>
                        </div>
                        <div class="mb-4">
                            <h5 class="m-0 me-2">Assign Events</h5>
                            @if($user)
                                <div class="form-check mt-4">
                                    <input class="form-check-input show-assigned" type="checkbox" value="1"
                                           id="show-assigned" checked>
                                    <label class="form-check-label" for="show-assigned"> Show Assigned Only </label>
                                </div>
                            @endif
                            <div class="invalid-feedback w-100" id="checkbox-feedback">
                                Please select at least one option.
                            </div>
                            <div class="table-responsive" id="table-scrollable">

                            </div>
                        </div>
                        <div class="d-flex justify-content-between mt-3">
                            <button type="submit" class="btn btn-primary">{{ $user?'Update':'Add'}} User</button>
                            <a href="{{ route('admin.users') }}" class="btn btn-label-primary">Back to List</a>
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
        <style>
            #table-scrollable{
                overflow-x: visible;
            }
        </style>
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
    <script type="module/javascript" id="placeholder-template">
        @include('admin.users.elements.placeholder')
    </script>
        <script type="text/javascript">
            (function() {
                'use strict';
                $('.select2').select2();
                $('.select2-multiple').select2({multiple: true});

                // new PerfectScrollbar('#table-scrollable', {
                //     wheelPropagation: false
                // });

                const flatpickrEndDate = {};

                $('.enable-password').change(function() {

                    let passwordFieldGroup = $($(this).data('password-group'));
                    if ($(this).is(':checked')) {
                        passwordFieldGroup.removeClass('d-none');
                    } else {
                        passwordFieldGroup.addClass('d-none');
                    }
                }).trigger('change');

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

                $('select[name="client[]"]').change(function(e){
                    let client = $(this).val();

                    const eventContainer = $('#table-scrollable');

                    eventContainer.html($('#placeholder-template').html());

                    $.get($(this).data('event-url'), { client }, function(response){
                        eventContainer.html(response);
                        setTimeout(function(){
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
                                $('[data-bs-toggle="tooltip"]').tooltip();
                                let dataItem = $(this).attr('data-item');
                                flatpickrEndDate[dataItem] = $(this).flatpickr({
                                    monthSelectorType: 'static',
                                    static: true
                                });
                            });
                        },400);
                    });
                }).change();

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
                        //validateEventSelection();
                    }
                })

                // Fetch all the forms we want to apply custom Bootstrap validation styles to
                var forms = document.querySelectorAll('.needs-validation');

                // Loop over them and prevent submission
                Array.prototype.slice.call(forms)
                    .forEach(function(form) {

                        form.addEventListener('submit', function(event) {

                            const checkedItem = $('input[name="event[]"]:checked');
                            //validateEventSelection();
                            //if (!form.checkValidity() || checkedItem.length === 0) {
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
