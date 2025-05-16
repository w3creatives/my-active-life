
<x-admin-layout>
    <div class="row">
        <div class="col-12">
            <table id="eventlist-table" class="table table-striped" data-ajax-url="{{ route('admin.events') }}">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>

                </tbody>
            </table>
        </div>
    </div>
    @push('stylesheet')
        <link href="https://cdn.datatables.net/2.3.1/css/dataTables.bootstrap5.css" type="text/css" />

    @endpush

    @push('scripts')
        <script src="https://cdn.datatables.net/2.3.1/js/dataTables.js" type="text/javascript"></script>
        <script src="https://cdn.datatables.net/2.3.1/js/dataTables.bootstrap5.js" type="text/javascript"></script>

        <script src="{{ asset('/js/events/list.js') }}" type="text/javascript"></script>
    @endpush

</x-admin-layout>
