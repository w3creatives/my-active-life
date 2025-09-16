<x-admin-layout title="{{ $event?'Update':'Add'}} Event">

    <div class="row g-6">

        <div class="col-md-12">

            <div class="card card-action mb-5">
                <div class="card-header">
                    <h5 class="card-action-title mb-0">{{ $event->name }} Tutorials</h5>
                    <div class="card-action-element">
                        <ul class="list-inline mb-0">
                            <li class="list-inline-item">
                                <a href="{{ route('admin.events.edit', $event->id) }}" class="btn btn-label-primary">Edit
                                    Event</a>
                            </li>
                            <li class="list-inline-item">
                                <a href="{{ route('admin.events') }}" class="btn btn-label-primary">Back to Events</a>
                            </li>
                            @if($eventTutorial)
                                <li class="list-inline-item">
                                    <form method="POST" id="delete-tutorial-form" action="{{ route('admin.events.tutorials.delete',[$event->id, $eventTutorial->id]) }}">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                    <button class="btn btn-label-danger delete-tutorial">Delete
                                        Tutorial
                                    </button>
                                </li>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>
            <x-alert.validation :errors=$errors></x-alert.validation>
            <form action="" class="needs-validation" method="POST" id="event-form" enctype="multipart/form-data"
                  novalidate>
                @csrf
                <div class="tutorial-list">
                    @if($tutorials->count())
                        @foreach($tutorials as $inputIndex => $tutorial)
                            @include('admin.events.tutorials.tutorial-form', ['tutorial' => $tutorial, 'inputIndex' => $inputIndex])
                        @endforeach
                    @else
                        @if(old('type'))
                            @foreach(old('type') as $oldKey => $oldType)
                                @include('admin.events.tutorials.tutorial-form', ['tutorial' => (object)[
                                     'type' => $oldType,
                                    'content' => old('content.'.$oldKey),
                                    'level' => old('level.'.$oldKey),
                                    'source' => old('source.'.$oldKey),
                                    'thumb' => old('thumb.'.$oldKey),
                                    'title' => old('title.'.$oldKey),
                                    'url' => old('url.'.$oldKey),
                            ]])
                            @endforeach
                            @else
                                @include('admin.events.tutorials.tutorial-form', ['tutorial' => null])
                            @endif
                    @endif
                </div>
                <div class="d-flex justify-content-between mt-3">
                    <button type="submit" class="btn btn-primary">Save Tutorial</button>
                    <a class="btn btn-link add-new-tutorial">
                        <i class="icon-base ti tabler-plus"></i>
                        <span class="align-middle">Add new</span>
                    </a>
                </div>
            </form>

        </div>
    </div>
    @push('stylesheets')
        <link rel="stylesheet" href="{{ asset('assets/vendor/libs/@form-validation/form-validation.css') }}">
        <link rel="stylesheet" href="{{ asset('assets/vendor/libs/flatpickr/flatpickr.css') }}" />
        <link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
        <link rel="stylesheet" href="{{ asset('assets/vendor/libs/sweetalert2/sweetalert2.css') }}" />
    @endpush
    @push('scripts')
        {{-- <script src="{{ asset('assets/js/plugins/parsley.min.js') }}"></script>--}}
        <script src="{{ asset('assets/vendor/libs/@form-validation/popular.js') }}"></script>
        <script src="{{ asset('assets/vendor/libs/@form-validation/bootstrap5.js') }}"></script>
        <script src="{{ asset('assets/vendor/libs/@form-validation/auto-focus.js') }}"></script>
        <script src="{{ asset('assets/vendor/libs/moment/moment.js') }}"></script>
        <script src="{{ asset('assets/vendor/libs/flatpickr/flatpickr.js') }}"></script>
        <script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
        <script src="{{ asset('assets/vendor/libs/sweetalert2/sweetalert2.js') }}"></script>
        <script type="text/template" id="tutorial-form-item">
        @include('admin.events.tutorials.tutorial-form',  ['tutorial' => null])
        </script>
        <script type="text/javascript">
            (function() {
                'use strict';

                $('.select2').select2();

                $(document).on('click', '.tutorial-remove', function(e) {
                    e.preventDefault();

                    let tutorialItem = $(this).parents('.divider').parent().parent();

                    Swal.fire({
                        title: 'Are you sure want to remove this item?',
                        // text: 'It will remove item from the list',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, remove it!',
                        customClass: {
                            confirmButton: 'btn btn-danger',
                            cancelButton: 'btn btn-label-secondary'
                        },
                        buttonsStyling: false
                    }).then(function(result) {
                        if (result.value) {
                            tutorialItem.remove();
                        }
                    });
                });

                $('.delete-tutorial').click(function(e) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Are you sure?',
                        text: 'You won\'t be able to revert this!',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, delete it!',
                        customClass: {
                            confirmButton: 'btn btn-danger',
                            cancelButton: 'btn btn-label-secondary'
                        },
                        buttonsStyling: false
                    }).then(function(result) {
                        if (result.value) {
                            $('#delete-tutorial-form').submit();
                        }
                    });
                });

                $(document).on('change', '.input-group-selection', function(e) {
                    let val = $(this).val();

                    let tutorialElement = $(this).parents('.tutorial-input-group');

                    let inputGroupItem = tutorialElement.find('.input-group-item');

                    let selectedInputGroupItem = tutorialElement.find(`.input-group-item-${val}`);

                    inputGroupItem.addClass('d-none');

                    inputGroupItem.each(function(){
                        $(this).find('input,select').attr('data-validate',false);
                    });

                    selectedInputGroupItem.each(function(){
                        $(this).find('input,select').attr('data-validate',true);
                    });

                    selectedInputGroupItem.removeClass('d-none');
                });

                $(document).find('.input-group-selection').each(function() {
                    $(this).trigger('change');
                });

                $('.add-new-tutorial').click(function(e) {
                    e.preventDefault();
                    $('.tutorial-list').append($('#tutorial-form-item').html());

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
