<x-admin-layout>
    <div class="row g-6">

        <div class="col-md-12">
            <div class="card">
                <h5 class="card-header">{{ $user?'Update':'Add'}} User Details</h5>
                <div class="card-body">
                    <x-alert.validation :errors=$errors></x-alert.validation>
                    <form action="" class="needs-validation" method="POST" id="event-form" autocomplete="nope" novalidate>
                        <input type="email" style="display:none">
                        <input type="password" style="display:none">
                        @csrf
                        <div class="row">
                            <div class="mb-4 col-xl-6 col-sm-12">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" id="first_name" name="first_name"
                                       value="{{ $user->first_name??old('first_name') }}"
                                       class="form-control @error('first_name') parsley-error @enderror"
                                       data-parsley-trigger="change" required>
                            </div>
                            <div class="mb-4 col-xl-6 col-sm-12">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" id="last_name" name="last_name"
                                       value="{{ $user->last_name??old('last_name') }}"
                                       class="form-control @error('last_name') parsley-error @enderror"
                                       data-parsley-trigger="change" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="mb-4 col-xl-6 col-sm-12">
                                <label for="display_name" class="form-label">Display Name</label>
                                <input type="text" name="display_name" id="display_name"
                                       value="{{ $user->display_name??old('display_name') }}" class="form-control" required>
                            </div>
                            <div class="mb-4 col-xl-6 col-sm-12">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" name="email" id="email"
                                       value="{{ $user->email??old('email') }}" class="form-control" autocomplete="nope" required>
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
                                   value="{{ old('password') }}" class="form-control" autocomplete="nope">
                        </div>
                        <div class="mb-4 password-group col-xl-6 col-sm-12">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" name="confirm_password" id="confirm_password"
                                   value="{{ old('confirm_password') }}" class="form-control"  autocomplete="nope">
                        </div>
                        </div>
                        <div class="mb-4">
                            <h5 class="m-0 me-2">Assign Events</h5>
                            @if($user)
                            <div class="form-check mt-4">
                                <input class="form-check-input show-assigned" type="checkbox" value="1" id="show-assigned" checked>
                                <label class="form-check-label" for="show-assigned"> Show Assigned Only </label>
                            </div>
                            @endif
                            <div class="table-responsive overflow-hidden" style="height: 300px" id="table-scrollable">
                                <table class="table card-table">
                                    <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Event Start/End Date</th>
                                    </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0">
                                    @foreach($events as $event)
                                        @php
                                            $subscriptionStartDate = $event->hasUserParticipation($user,false, 'subscription_start_date');
                                            $subscriptionEndDate = $event->hasUserParticipation($user,false, 'subscription_end_date');
                                        @endphp
                                        <tr class="{{ $event->hasUserParticipation($user)?'user-assigned':'user-unassigned' }}">
                                            <td class="w-50 ps-0 pt-0">
                                                <div class="d-flex justify-content-start align-items-center">
                                                    <div class="form-check mt-4">
                                                        <input class="form-check-input" type="checkbox" name="event[]"
                                                               value="{{ $event->id }}" id="event-item-{{ $event->id }}"
                                                               {{ $event->hasUserParticipation($user)?'checked':'' }} data-end-item="subscription-item-{{$event->id}}" {{ $event->isPastEvent()?'disabled':'' }}>
                                                        <label class="form-check-label"
                                                               for="event-item-{{ $event->id }}"> {{ $event->name }}</label>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-end pe-0 text-nowrap">
                                                <input type="date" name="start_date[{{$event->id}}]"
                                                       class="form-control start-date @error('start_date') parsley-error @enderror" data-item="subscription-item-{{$event->id}}" value="{{ $subscriptionStartDate?$subscriptionStartDate:$event->start_date }}" {{ $event->isPastEvent()?'disabled':'' }} required
                                                       data-parsley-trigger="change" placeholder="YYYY-MM-DD">
                                            </td>
                                            <td class="text-end pe-0 text-nowrap">
                                                <input type="date" name="end_date[{{$event->id}}]" data-item="subscription-{{$event->id}}"
                                                       class="form-control end-date @error('end_date') parsley-error @enderror" {{ $event->isPastEvent()?'disabled':'' }} required
                                                       data-parsley-trigger="change" placeholder="YYYY-MM-DD" value="{{ $subscriptionEndDate?$subscriptionEndDate:$event->end_date }}">

                                            </td>

                                            <td class="text-end pe-0 text-nowrap">
                                                <h6 class="mb-0   text-{{ $event->isPastEvent()?'danger':'' }}">{{ \Carbon\Carbon::parse($event->start_date)->format('m/d/Y') }}
                                                    - {{ \Carbon\Carbon::parse($event->end_date)->format('m/d/Y') }}</h6>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
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
                        console.log(dataItem, $(e.element))
                        flatpickrEndDate[dataItem].set("minDate", date_str);
                    }
                });
                $('.end-date').each(function(){
                    let dataItem = $(this).attr('data-item');
                    flatpickrEndDate[dataItem] =  $(this).flatpickr({
                        monthSelectorType: 'static',
                        static: true
                    });
                });

                $('.enable-password').change(function() {

                    let passwordFieldGroup = $($(this).data('password-group'));
                    if ($(this).is(':checked')) {
                        passwordFieldGroup.removeClass('d-none');
                    } else {
                        passwordFieldGroup.addClass('d-none');
                    }
                }).trigger('change');

                if($('.show-assigned').length) {
                    $('.show-assigned').change(function(){
                        let userUnassignedItems = $('.user-unassigned');
                        if($(this).is(':checked')) {
                            userUnassignedItems.addClass('d-none');
                        } else {
                            userUnassignedItems.removeClass('d-none');
                        }
                    }).trigger('change');
                }

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
