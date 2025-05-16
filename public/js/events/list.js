$(function(){
    $.fn.dataTable.ext.errMode = 'none';
    let eventsTableId = '#eventlist-table';
    let eventsTable = $(eventsTableId);

    new DataTable(eventsTableId, {
        ajax: eventsTable.data('ajax-url'),
        processing: true,
        serverSide: true,
        lengthChange: true,
        columns: [
            { data: 'id', name: 'id' },
            { data: 'name', name: 'name' },
            { data: 'start_date', name: 'start_date' },
            { data: 'end_date', name: 'end_date' },
            { data: 'action', name: 'action', sortable: false },
        ]
    });

});
