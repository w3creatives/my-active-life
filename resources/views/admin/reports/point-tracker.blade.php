<x-admin-layout title="Source Point Tracker">
    <div class="card">
        <div class="row card-header flex-column flex-md-row border-bottom mx-0 px-3">
            <div class="d-md-flex justify-content-between align-items-center dt-layout-start col-md-auto me-auto mt-0">
                <div class="card-title d-flex justify-content-between">
                    <h5 class="pb-0 text-md-start text-center p-0 m-0">Source Point Tracker</h5>
                </div>
            </div>
            <div class="d-md-flex justify-content-between align-items-center dt-layout-end col-md-auto ms-auto mt-0">

            </div>
        </div>
        <div class="card-datatable text-nowrap">
            <table class="datatables-ajax table table-bordered" id="pointtracker-table"
                   data-ajax-url="{{ route('admin.reports.point-tracker') }}">
                <thead>
                <tr>
                    <th>Source</th>
                    <th>Total Points</th>
                    <th>Date</th>
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
            src="{{ asset('js/common/custom.datatable.js') }}"></script>
        <script type="text/javascript">
            $(function() {
                customDatatable.initDatatable('#pointtracker-table', [
                    { data: 'source', name: 'source' },
                    { data: 'total_point', name: 'total_point' },
                    { data: 'date', name: 'date' }
                ]);
            });
        </script>
    @endpush

</x-admin-layout>
