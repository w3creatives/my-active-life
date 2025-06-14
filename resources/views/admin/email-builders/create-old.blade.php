<x-admin-layout>

    <div class="row g-6">
        <div class="col-md-12">
            <div class="card card-action">
                <div class="card-header">
                    <h5 class="card-action-title mb-0">Add Email Template</h5>
                    <div class="card-action-element">
                        <ul class="list-inline mb-0">
                            <li class="list-inline-item">
                                <a href=""
                                   class="btn btn-label-primary">Back to List</a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <div id="editor-container" style="height: 700px;"></div>
                        </div>
                        <div class="mb-4 col-xl-4 col-sm-12 col-md-6">
                            <label for="sponsor" class="form-label">Tea</label>
                            <input type="text" id="sponsor" name="sponsor" class="form-control"
                                   value="{{ $activity->sponsor ?? old('sponsor') }}" required>
                        </div>
                </div>

            </div>
        </div>
    </div>
    @push('scripts')
        <script src="https://editor.unlayer.com/embed.js"></script>
        <script type="text/javascript">
            (function() {
                'use strict';
                unlayer.init({
                    id: 'editor-container', // ID of the container created in previous step
                    projectId: 1234, // Add your project ID here
                    displayMode: 'email', // Can be 'email', 'web' or 'popup'
                    appearance: {
                        theme: 'modern_dark',
                    },
                    editor: {
                        confirmOnDelete: true,
                    }
                });
                unlayer.exportHtml(function (data) {
                    var json = data.design; // The design JSON structure
                    var html = data.html; // The final HTML of the design

                    // Save the JSON and/or HTML
                });
            })();

        </script>
    @endpush
</x-admin-layout>
