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
            <table class="datatables-ajax table table-bordered report-table" data-ajax-url="{{ route('admin.reports.events',$table['type']) }}" id="{{ $table['id'] }}" data-columns='[{ "data":"id", "name":"id" },{ "data":"name", "name":"name" },{ "data":"event_type_text", "name":"event_type_text" },{ "data":"start_date", "name":"start_date" },{ "data":"end_date", "name":"end_date" },{ "data":"participations_count","name":"participations_count","sortable":false}]'>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Event Type</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>User Count</th>
                </tr>
                </thead>
                <tbody>

                </tbody>
            </table>
        </div>
    </div>
</div>
