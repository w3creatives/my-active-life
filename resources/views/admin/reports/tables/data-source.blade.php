<div class="col-lg-12 col-sm-12">
    <div class="card">
        <div class="row card-header flex-column flex-md-row border-bottom mx-0 px-3">
            <div
                class="d-md-flex justify-content-between align-items-center dt-layout-start col-md-auto me-auto mt-0">
                <div class="card-title d-flex justify-content-between">
                    <h5 class="pb-0 text-md-start text-center p-0 m-0">{{ $table['title'] }}</h5>
                </div>
            </div>
        </div>
        <div class="card-datatable text-nowrap">
            <table class="datatables-ajax table table-bordered report-table"
                   data-ajax-url="{{ route('admin.reports.datasources') }}"
                   id="{{ $table['id'] }}"
                   data-columns='[{"data": "id", "name": "id"},{"data": "name", "name": "name"},{"data": "logo", "name": "logo"},{"data": "description", "name": "description"},{"data": "users_count", "name": "users_count", "sortable": false}]'>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Logo</th>
                    <th>Description</th>
                    <th width="100">User Count</th>
                </tr>
                </thead>
                <tbody>

                </tbody>
            </table>
        </div>
    </div>
</div>
