<x-admin-layout>
    <div class="card">
        <div class="row card-header flex-column flex-md-row border-bottom mx-0 px-3">
            <div class="d-md-flex justify-content-between align-items-center dt-layout-start col-md-auto me-auto mt-0">
                <div class="card-title d-flex justify-content-between">
                    <h5 class="pb-0 text-md-start text-center p-0 m-0">#{{ $event->id }}: {{ $event->name }}
                        Activities</h5>
                </div>
            </div>
            <div class="d-md-flex justify-content-between align-items-center dt-layout-end col-md-auto ms-auto mt-0">
                <div class="dt-buttons btn-group flex-wrap mb-0">
                    <a href="{{ route('admin.events') }}" class="btn btn-label-primary me-4">Back to Events</a>

                    <a
                       class="btn create-new btn-primary" tabindex="0" data-bs-toggle1="offcanvas"
                       data-bs-target1="#action-activity-modal" data-action-title="Add New Activity" href="{{ route('admin.events.activities.create',[$event->id,null]) }}"
                       aria-controls="activitylist-table"
                       type="button">
                        <span>
                            <span class="d-flex align-items-center gap-2">
                                <i class="icon-base ti tabler-plus icon-sm"></i>
                                <span class="d-none d-sm-inline-block">Add New Activity</span>
                            </span>
                        </span>
                    </a>
                </div>
            </div>
        </div>
        <div class="card-datatable text-nowrap">
            <table class="datatables-ajax table table-bordered" id="activitylist-table"
                   data-ajax-url="{{ route('admin.events.activities', $event->id) }}">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Sponsor</th>
                    <th>Category</th>
                    <th>Group</th>
                    <th>Name</th>
                    <th>Available From</th>
                    <th>Available Until</th>
                    <th>Action</th>
                </tr>
                </thead>
            </table>
        </div>
    </div>

    @push('modals')

        <x-offcanvas id="action-activity-modal" title="Activity" ajax-content="true">
            TEST
        </x-offcanvas>
    @endpush
    @push('stylesheets')
        <link rel="stylesheet"
              href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
        <link rel="stylesheet"
              href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
        <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css') }}" />
        <link rel="stylesheet" href="{{ asset('assets/vendor/libs/sweetalert2/sweetalert2.css') }}" />
    @endpush
    @push('scripts')
        <script
            src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
        <script src="{{ asset('assets/vendor/libs/sweetalert2/sweetalert2.js') }}"></script>
        <script src="{{ asset('js/common/custom.datatable.js') }}"></script>
        <script type="text/javascript">
            $(function() {
                customDatatable.initDatatable('#activitylist-table', [
                    {
                        data: 'id',
                        name: 'id'
                    },
                    {
                        data: 'sponsor',
                        name: 'sponsor'
                    },
                    {
                        data: 'category',
                        name: 'category'
                    },
                    {
                        data: 'group',
                        name: 'group'
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'available_from',
                        name: 'available_from'
                    },
                    {
                        data: 'available_until',
                        name: 'available_until'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        sortable: false
                    }
                ]);

                $('.ajax-canvas').on('show.bs.offcanvas', function(event) {
                    let button = $(event.relatedTarget); // Button that triggered the modal
                    let offcanvas = $(this);

                    let title = button.data('action-title');

                    offcanvas.find('.offcanvas-title').html(title);

                    let offcanavasBody = offcanvas.find('.offcanvas-body');

                    offcanvas.find('.offcanvas-title').html(button.data('action-title'));

                    offcanavasBody.html(`<div class="text-center"><div class="spinner-border text-secondary" role="status"><span class="visually-hidden">Loading...</span></div></div>`);

                    $.get(button.data('ajax-url'), {}, function(data) {
                        offcanavasBody.html(data.html);

                    });
                });

                $(this).find('#event-form').on('submit',function(e){
                    let form = $(this);

                   console.log(form.checkValidity())
                });

                $(this).on('click','.action-delete', function(){

                    let actionForm = $($(this).data('confirm-form'));

                    Swal.fire({
                        title: 'Are you sure?',
                        text: "You won't be able to revert this!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, delete it!',
                        customClass: {
                            confirmButton: 'btn btn-danger',
                            cancelButton: 'btn btn-label-secondary'
                        },
                        buttonsStyling: false
                    }).then(function (result) {
                        if (result.value) {
                            actionForm.submit();
                        }
                    });
                });

            });
        </script>
    @endpush


</x-admin-layout>
