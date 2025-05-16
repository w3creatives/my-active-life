
<x-admin-layout>
<div class="row">
    <div class="col-12">
    <table id="userlist-table" class="table table-striped" data-ajax-url="{{ route('admin.users') }}">
        <thead>
        <tr>
            <th>
                First Name</th>
            <th>
                Last Name</th>
            <th>Email</th>
            <th>Display Name</th>
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

        <script src="{{ asset('/js/users/list.js') }}" type="text/javascript"></script>
    @endpush

</x-admin-layout>
