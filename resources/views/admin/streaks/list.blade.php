<x-admin-layout>
    <div class="card">
        <div class="row card-header flex-column flex-md-row border-bottom mx-0 px-3">
            <div class="d-md-flex justify-content-between align-items-center dt-layout-start col-md-auto me-auto mt-0">
                <div class="card-title d-flex justify-content-between">
                    <h5 class="pb-0 text-md-start text-center p-0 m-0">#{{ $event->id }}: {{ $event->name }}
                        Streaks</h5>
                </div>
            </div>
            <x-event.list-action :event="$event"></x-event.list-action>
        </div>
        <div class="card-datatable text-nowrap">
            <table class="datatables-ajax table table-bordered" id="milestonelist-table"
                   data-ajax-url="">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Days Count</th>
                    <th>Min Distance</th>
                    <th>Action</th>
                </tr>
                </thead>
            </table>
        </div>
    </div>

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
                        data: 'days_count',
                        name: 'days_count'
                    },
                    {
                        data: 'min_distance',
                        name: 'min_distance',
                        sortable: false
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
