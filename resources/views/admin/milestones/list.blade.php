<x-admin-layout>
    <div class="card">
        <div class="row card-header flex-column flex-md-row border-bottom mx-0 px-3">
            <div class="d-md-flex justify-content-between align-items-center dt-layout-start col-md-auto me-auto mt-0">
                <div class="card-title d-flex justify-content-between">
                    <h5 class="pb-0 text-md-start text-center p-0 m-0">#{{ $event->id }}: {{ $event->name }}
                        Milestones</h5>
                </div>
            </div>
            <div class="d-md-flex justify-content-between align-items-center dt-layout-end col-md-auto ms-auto mt-0">
                <div class="dt-buttons btn-group flex-wrap mb-0">
                    <a href="{{ route('admin.events') }}" class="btn btn-label-primary me-4">Back to Events</a>
                        
                    <a href="{{ route('admin.events.milestones.create',$event->id) }}"
                        class="btn create-new btn-primary" tabindex="0" data-bs-toggled="offcanvas"
                        data-bs-targetd="#action-milestone-modal" data-action-title="Add Milestone"
                        aria-controls="eventlist-table"
                        type="button">
                        <span>
                            <span class="d-flex align-items-center gap-2">
                                <i class="icon-base ti tabler-plus icon-sm"></i>
                                <span class="d-none d-sm-inline-block">Add New Milestone</span>
                            </span>
                        </span>
                    </a>
                </div>
            </div>
        </div>
        <div class="card-datatable text-nowrap">
            <table class="datatables-ajax table table-bordered" id="milestonelist-table"
                data-ajax-url="{{ route('admin.events.milestones', $event->id) }}">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th> Distance</th>
                        <th>Logo</th>
                        <th>Data</th>
                        <th>Action</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    @push('modals')
    <x-modal id="view-milestone-modal" title="Milestone Details" ajax-content="true">
        @push('modal-footer')
        <div class="d-flex justify-content-between mt-3">
        
            <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
        </div>
        @endpush
    </x-modal>
    <x-offcanvas id="action-milestone-modal" title="Milestone" ajax-content="true">
        TEST
    </x-offcanvas>
    @endpush
    @push('stylesheets')
    <link rel="stylesheet"
        href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet"
        href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css') }}" />
    @endpush
    @push('scripts')
    <script
        src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script
        src="{{ asset('js/common/custom.datatable.js') }}"></script>
    <script type="text/javascript">
        $(function() {
            customDatatable.initDatatable('#milestonelist-table', [{
                    data: 'id',
                    name: 'id'
                },
                {
                    data: 'name',
                    name: 'name'
                },
                {
                    data: 'distance',
                    name: 'distance'
                },
                {
                    data: 'logo',
                    name: 'logo',
                    sortable: false,
                    width: 125
                },
                {
                    data: 'data',
                    name: 'data',
                    sortable: false
                },
                {
                    data: 'action',
                    name: 'action',
                    sortable: false
                }
            ]);

            $('#action-milestone-modal').on('show.bs.offcanvas', function(event) {
                var button = $(event.relatedTarget); // Button that triggered the modal
                var offcanvas = $(this);

                var offcanavasBody = offcanvas.find('.offcanvas-body');

                offcanvas.find('.offcanvas-title').html(button.data('action-title'));

                offcanavasBody.html(`<div class="text-center"><div class="spinner-border text-secondary" role="status"><span class="visually-hidden">Loading...</span></div></div>`);

                $.get(button.attr('href'), {}, function(data) {
                    offcanavasBody.html(data.html);
                });
            });

            $('.ajax-modal').on('show.bs.modal', function(event) {
                var button = $(event.relatedTarget); // Button that triggered the modal
                var modal = $(this);

                var modalBody = modal.find('.modal-body');

                modalBody.html(`<div class="text-center"><div class="spinner-border text-secondary" role="status"><span class="visually-hidden">Loading...</span></div></div>`);

                $.get(button.attr('href'), {}, function(data) {
                    modalBody.html(data.html);
                });
            });
        });
    </script>
    @endpush


</x-admin-layout>