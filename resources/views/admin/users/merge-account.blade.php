<x-admin-layout>
    <div class="row g-6">

        <div class="col-md-8 offset-2">
            <div class="card">
                <h5 class="card-header">Merge User Accounts</h5>
                <div class="card-body">
                    <x-alert.validation :errors=$errors></x-alert.validation>
                    <form action="" class="needs-validation" method="POST" id="merge-account-form" novalidate>
                        @csrf
                        <div class="row">
                            <div class="mb-4 col-xl-12 col-sm-12">
                                <label for="primary-account-email" class="form-label">Email of Primary Account</label>
                                <input type="email" name="primary_account_email" id="primary-account-email"
                                       value="{{ old('primary_account_email') }}" class="form-control" required>
                                <div id="primary-account-email-help" class="form-text">
                                    This account will be kept and all content merged to it.
                                </div>
                            </div>
                            <div class="mb-4 col-xl-12 col-sm-12">
                                <label for="secondary-account-email" class="form-label">Email of Secondary Account</label>
                                <input type="email" name="secondary_account_email" id="secondary-account-email"
                                       value="{{ old('secondary_account_email') }}" class="form-control" required>
                                <div id="secondary-account-email-help" class="form-text">
                                    This account will be removed, all contents will be put into the primary account.
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-3">
                            <button type="submit" class="btn btn-primary">Merge Accounts</button>
                            <a href="{{ route('admin.users') }}" class="btn btn-label-primary">Back to List</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @push('stylesheets')
        <link rel="stylesheet" href="{{ asset('assets/vendor/libs/@form-validation/form-validation.css') }}">
    @endpush
    @push('scripts')
        <script src="{{ asset('assets/vendor/libs/@form-validation/popular.js') }}"></script>
        <script src="{{ asset('assets/vendor/libs/@form-validation/bootstrap5.js') }}"></script>
        <script src="{{ asset('assets/vendor/libs/@form-validation/auto-focus.js') }}"></script>
        <script type="text/javascript">
            (function() {
                'use strict';

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
