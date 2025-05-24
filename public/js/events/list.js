$(function () {
    $.fn.dataTable.ext.errMode = 'none';
    let eventsTableId = '#eventlist-table';
    let eventsTable = document.getElementById('eventlist-table');

      const dataTableSearch = new simpleDatatables.DataTable(eventsTableId, {
      searchable: true,
      fixedHeight: true
    });
    console.log(dataTableSearch)

    /*
    new DataTable(eventsTableId, {
        searchable: true,
        fixedHeight: true,
        ajax: eventsTable.getAttribute('data-ajax-url'),
        processing: true,
        serverSide: true,
        columns: [
            { data: 'id', name: 'id' },
            { data: 'name', name: 'name' },
            { data: 'start_date', name: 'start_date' },
            { data: 'end_date', name: 'end_date' },
            { data: 'action', name: 'action', sortable: false },
        ]
    });*/
});
