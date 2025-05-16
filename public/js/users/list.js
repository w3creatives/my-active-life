$(function(){
    $.fn.dataTable.ext.errMode = 'none';
    let usersTableId = '#userlist-table';
    let usersTable = $(usersTableId);

    new DataTable(usersTableId, {
        ajax: usersTable.data('ajax-url'),
        processing: true,
        serverSide: true,
        lengthChange: true,
        columns: [
            { data: 'first_name', name: 'first_name' },
            { data: 'last_name', name: 'last_name' },
            { data: 'email', name: 'email' },
            { data: 'display_name', name: 'display_name' },
            { data: 'action', name: 'action', sortable: false },
        ]
    });

});
