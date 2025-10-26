<x-admin-layout title="{{ $item?'Update':'Add'}} Quest Group">

    <div class="row g-6">

        <div class="col-md-12">
            <div class="card card-action">
                <div class="card-header">
                    <h5 class="card-action-title mb-0">{{ $item?'Update':'Add'}} Quest Group</h5>
                    <div class="card-action-element">
                        <ul class="list-inline mb-0">
                            <li class="list-inline-item">
                                <a href="{{ route('admin.quests.groups') }}" class="btn btn-label-primary">Back to List</a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <x-alert.validation :errors=$errors></x-alert.validation>
                    <form action="" class="needs-validation" method="POST" id="event-form" enctype="multipart/form-data" novalidate>
                        @csrf
                        <div class="row">

                            <div class="mb-4 col-xl-4 col-sm-12 col-md-6">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" id="name" name="name" value="{{ $item->name??old('name') }}"
                                       class="form-control @error('name') parsley-error @enderror"
                                       data-parsley-trigger="change" required>
                            </div>
                            <div class="mb-4 col-xl-4 col-sm-12 col-md-6">
                                <label for="logo" class="form-label">Logo</label>
                                <input type="file" id="logo" name="logo" data-preview=".logo-preview"
                                       class="form-control choose-file">

                            </div>
                            <div class="mb-4 col-xl-4 col-sm-12 col-md-6">
                                <div
                                    class="logo-preview {{ (($item && !$item->logo_url) || !$item)?'d-none':'' }} mt-3">
                                    <img src="{{ $item->logo_url??'' }}" class="img-fluid img"
                                         style="height: 100px;" onerror="this.src='{{ url('/images/default-placeholder.png') }}'"/>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between mt-3">
                            <button type="submit" class="btn btn-primary">{{ $item?'Update':'Add'}} Group</button>

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
        {{-- <script src="{{ asset('assets/js/plugins/parsley.min.js') }}"></script>--}}
        <script src="{{ asset('assets/vendor/libs/@form-validation/popular.js') }}"></script>
        <script src="{{ asset('assets/vendor/libs/@form-validation/bootstrap5.js') }}"></script>
        <script src="{{ asset('assets/vendor/libs/@form-validation/auto-focus.js') }}"></script>
        <script type="text/javascript">
            (function() {
                'use strict';

                function readURL(input) {
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
