<x-admin-layout>
    <div class="card">
        <div class="row card-header flex-column flex-md-row border-bottom mx-0 px-3">
            <div class="d-md-flex justify-content-between align-items-center dt-layout-start col-md-auto me-auto mt-0">
                <div class="card-title d-flex justify-content-between">
                    <h5 class="pb-0 text-md-start text-center p-0 m-0">Clients</h5>
                </div>
            </div>
            <div class="d-md-flex justify-content-between align-items-center dt-layout-end col-md-auto ms-auto mt-0">
                <div class="dt-buttons btn-group flex-wrap mb-0">
                    <a href="{{ route('admin.clients.create') }}" class="btn create-new btn-primary" tabindex="0"
                       aria-controls="eventlist-table"
                       type="button">
                        <span>
                            <span class="d-flex align-items-center gap-2">
                                <i class="icon-base ti tabler-plus icon-sm"></i>
                                <span class="d-none d-sm-inline-block">Add New Client</span>
                            </span>
                        </span>
                    </a>
                </div>
            </div>
        </div>
        <div class="card-datatable text-nowrap">
            <table class="datatables-ajax table table-bordered" id="clientlist-table"
                   data-ajax-url="{{ route('admin.clients') }}">
                <thead>
                <tr>
                    <th width="50">
                        ID</th>
                    <th width="100">
                        Logo</th>
                    <th>Name</th>
                    <th>Address</th>
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
    @endpush
    @push('scripts')
        <script
            src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
        <script
            src="{{ asset('js/common/custom.datatable.js?v=1.0.0') }}"></script>
        <script type="text/javascript">
            $(function() {
                customDatatable.initDatatable('#clientlist-table', [
                    { data: 'id', name: 'id' },
                    { data: 'logo', name: 'logo' },
                    { data: 'name', name: 'name' },
                    { data: 'address', name: 'address' },
                    { data: 'action', name: 'action', sortable: false },
                ]);
            });
        </script>
    @endpush

</x-admin-layout>
