<x-admin-layout>

    <div class="row g-6">
        <div class="col-md-12">
            <div class="card card-action">
                <div class="card-header">
                    <h5 class="card-action-title mb-0">{{ $emailBuilder?'Update':'Create' }} Email Template</h5>
                    <div class="card-action-element">
                        <ul class="list-inline mb-0">
                            <li class="list-inline-item">
                                <a href="{{ route('admin.email.builders') }}"
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
                            <div class="mb-4 col-12">
                                <label for="name" class="form-label">Template Name</label>
                                <input type="text" id="name" name="name" class="form-control"
                                       value="{{ $emailBuilder->name ?? old('name') }}" required>
                            </div>
                            <div class="mb-4 col-12">
                                <label for="subject" class="form-label">Email Subject</label>
                                <input type="text" id="subject" name="subject" class="form-control"
                                       value="{{ $emailBuilder->subject ?? old('subject') }}" required>
                            </div>
                            <div class="mb-4 col-12">
                                <label for="title" class="form-label">Email Body</label>
                                <div id="text-editor"></div>
                                <textarea name="content" id="email-content"
                                          class="form-control d-none">{!! $emailBuilder->content ?? old('content') !!}</textarea>
                            </div>
                            <div class="d-flex justify-content-between mt-3">
                                <button type="submit" class="btn btn-primary">{{ $emailBuilder?'Update':'Create'}}
                                    Template
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @push('stylesheets')
            <link rel="stylesheet" href="{{ asset('assets/vendor/libs/highlight/highlight.css') }}" />
            <link rel="stylesheet" href="{{ asset('assets/vendor/libs/quill/editor.css') }}" />
        @endpush
        @push('scripts')
            <script src="{{ asset('assets//vendor/libs/highlight/highlight.js') }}"></script>
            <script src="{{ asset('assets/vendor/libs/quill/quill.js') }}"></script>
            <script type="text/javascript">
                (function() {
                    'use strict';

                    const editorToolbar = [

                        ['bold', 'italic', 'underline', 'strike'],
                        [
                            {
                                color: []
                            },
                            {
                                background: []
                            }
                        ],
                        [
                            {
                                script: 'super'
                            },
                            {
                                script: 'sub'
                            }
                        ],
                        [
                            {
                                header: '1'
                            },
                            {
                                header: '2'
                            },
                            {
                                header: '3'
                            },
                            'blockquote',
                            'code-block'
                        ],
                        [
                            {
                                list: 'ordered'
                            },
                            {
                                indent: '-1'
                            },
                            {
                                indent: '+1'
                            }
                        ],
                        [{ direction: 'rtl' }, { align: [] }],
                        ['link', 'image', 'video', 'formula'],
                        ['clean']
                    ];

                    const editor = new Quill('#text-editor', {
                        bounds: '#full-editor',
                        placeholder: 'Type Something...',
                        modules: {
                            syntax: true,
                            toolbar: editorToolbar
                        },
                        theme: 'snow'
                    });

                    let editorTextarea = $('#email-content');

                    editor.on('text-change', function(delta, oldDelta, source) {
                        editorTextarea.val(editor.container.firstChild.innerHTML.replace('<p><br></p>', ''));
                    });

                    editor.setContents(editor.clipboard.convert({ html: editorTextarea.val() }), 'silent');

                    var forms = document.querySelectorAll('.needs-validation');

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
