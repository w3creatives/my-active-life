<x-admin-layout>
    <div class="row g-6">

        <div class="col-md-8 offset-2">
            <div class="card">
                <h5 class="card-header">{{ $user?'Update':'Add'}} User Details</h5>
                <div class="card-body">
                    <x-alert.validation :errors=$errors></x-alert.validation>
                    <form action="" class="needs-validation" method="POST" id="event-form" novalidate>
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
                                       value="{{ $user->display_name??old('display_name') }}" class="form-control">
                            </div>
                            <div class="mb-4 col-xl-6 col-sm-12">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" name="email" id="email"
                                       value="{{ $user->email??old('email') }}" class="form-control">
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
                                   value="{{ $user->password??old('password') }}" class="form-control">
                        </div>
                        <div class="mb-4 password-group col-xl-6 col-sm-12">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" name="confirm_password" id="confirm_password"
                                   value="{{ $user->confirm_password??old('confirm_password') }}" class="form-control">
                        </div>
                        </div>
                        <div class="mb-4">
                            <h5 class="m-0 me-2">Assign Events</h5>
                            <div class="table-responsive overflow-hidden" style="height: 300px" id="table-scrollable">
                                <table class="table card-table">
                                    <tbody class="table-border-bottom-0">
                                    @foreach($events as $event)
                                        <tr>
                                            <td class="w-100 ps-0 pt-0">
                                                <div class="d-flex justify-content-start align-items-center">
                                                    <div class="form-check mt-4">
                                                        <input class="form-check-input" type="checkbox" name="event[]"
                                                               value="{{ $event->id }}"
                                                               {{ $event->hasUserParticipation($user)?'checked':'' }} id="event-item-{{ $event->id }}" {{ $event->isPastEvent()?'disabled':'' }}>
                                                        <label class="form-check-label"
                                                               for="event-item-{{ $event->id }}"> {{ $event->name }}</label>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-end pe-0 text-nowrap">
                                                <h6 class="mb-0 text-{{ $event->isPastEvent()?'danger':'success' }}">{{ $event->isPastEvent()?'Expired':'Active' }}</h6>
                                            </td>
                                            <td class="text-end pe-0 text-nowrap">
                                                <h6 class="mb-0">{{ \Carbon\Carbon::parse($event->start_date)->format('m/d/Y') }}
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
    @endpush
    @push('scripts')
        {{-- <script src="{{ asset('assets/js/plugins/parsley.min.js') }}"></script>--}}
        <script src="{{ asset('assets/vendor/libs/@form-validation/popular.js') }}"></script>
        <script src="{{ asset('assets/vendor/libs/@form-validation/bootstrap5.js') }}"></script>
        <script src="{{ asset('assets/vendor/libs/@form-validation/auto-focus.js') }}"></script>
        <script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
        <script src="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
        <script type="text/javascript">
            (function() {
                'use strict';
                $('.select2').select2();

                new PerfectScrollbar('#table-scrollable', {
                    wheelPropagation: false
                });

                $('.enable-password').change(function() {

                    let passwordFieldGroup = $($(this).data('password-group'));
                    if ($(this).is(':checked')) {
                        passwordFieldGroup.removeClass('d-none');
                    } else {
                        passwordFieldGroup.addClass('d-none');
                    }
                }).trigger('change');

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
