<x-admin-layout>
    <div class="card">
        <div class="row card-header flex-column flex-md-row border-bottom mx-0 px-3">
            <div class="d-md-flex justify-content-between align-items-center dt-layout-start col-md-auto me-auto mt-0">
                <div class="card-title d-flex justify-content-between">
                    <h5 class="pb-0 text-md-start text-center p-0 m-0">Email Templates</h5>
                </div>
            </div>
            <div class="d-md-flex justify-content-between align-items-center dt-layout-end col-md-auto ms-auto mt-0">
                <div class="dt-buttons btn-group flex-wrap mb-0">
                    <a href="{{ route('admin.email.builders') }}" class="btn btn-label-primary me-4">Back to List</a>

                    <a
                        class="btn create-new btn-primary" tabindex="0" href="{{ route('admin.email.builders.create') }}" type="button">
                        <span>
                            <span class="d-flex align-items-center gap-2">
                                <i class="icon-base ti tabler-plus icon-sm"></i>
                                <span class="d-none d-sm-inline-block">Add New</span>
                            </span>
                        </span>
                    </a>
                </div>
            </div>
        </div>
        <div class="card-datatable text-nowrap">
            <table class="datatables-ajax table table-bordered" id="template-table"
                   data-ajax-url="{{ route('admin.email.builders') }}">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Template Name</th>
                    <th>Subject</th>
                    <th>Created At</th>
                    <th>Updated At</th>
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
                customDatatable.initDatatable('#template-table', [
                    {
                        data: 'id',
                        name: 'id'
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'subject',
                        name: 'subject'
                    },
                    {
                        data: 'created',
                        name: 'created'
                    },
                    {
                        data: 'updated',
                        name: 'updated'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        sortable: false
                    }
                ]);

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
