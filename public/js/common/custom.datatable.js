class CustomDataTable {
    customTable = null;

    initDatatable(elementId, columns) {

        $.fn.dataTable.ext.errMode = 'none';
        let elementTable = $(elementId);

        this.customTable = new DataTable(elementId, {
            searchable: true,
            fixedHeight: true,
            ajax: elementTable.attr('data-ajax-url'),
            processing: true,
            serverSide: true,
            columns: columns,
            layout: {
                topStart: {
                    rowClass: 'row mx-0 px-3 my-0 justify-content-between border-bottom',
                    features: [
                        {
                            pageLength: {
                                menu: [10, 25, 50, 100],
                                text: 'Show_MENU_entries'
                            }
                        }
                    ]
                },
                topEnd: {
                    search: {
                        placeholder: ''
                    }
                },
                bottomStart: {
                    rowClass: 'row mx-3 justify-content-between',
                    features: ['info']
                },
                bottomEnd: 'paging'
            },
            language: {
                paginate: {
                    next: '<i class="icon-base ti tabler-chevron-right scaleX-n1-rtl icon-18px"></i>',
                    previous: '<i class="icon-base ti tabler-chevron-left scaleX-n1-rtl icon-18px"></i>',
                    first: '<i class="icon-base ti tabler-chevrons-left scaleX-n1-rtl icon-18px"></i>',
                    last: '<i class="icon-base ti tabler-chevrons-right scaleX-n1-rtl icon-18px"></i>'
                }
            },
            createdRow: function (row, data, dataIndex) {
               $(document).find('[data-bs-toggle="tooltip"]').tooltip();
            }
        });

        this.initDatatableStyle();
    }

    initDatatableStyle() {
        setTimeout(() => {
            
            const elementsToModify = [
                { selector: '.dt-buttons .btn', classToRemove: 'btn-secondary' },
                { selector: '.dt-search .form-control', classToRemove: 'form-control-sm', classToAdd: 'ms-4' },
                { selector: '.dt-length .form-select', classToRemove: 'form-select-sm' },
                { selector: '.dt-layout-table', classToRemove: 'row mt-2' },
                { selector: '.dt-layout-end', classToAdd: 'mt-0' },
                { selector: '.dt-layout-end .dt-search', classToAdd: 'mt-0 mt-md-6 mb-6' },
                { selector: '.dt-layout-start', classToAdd: 'mt-0' },
                { selector: '.dt-layout-end .dt-buttons', classToAdd: 'mb-0' },
                { selector: '.dt-layout-full', classToRemove: 'col-md col-12', classToAdd: 'table-responsive' }
            ];

            // Delete record
            elementsToModify.forEach(({ selector, classToRemove, classToAdd }) => {
                document.querySelectorAll(selector).forEach((element) => {
                    if (classToRemove) {
                        classToRemove.split(' ').forEach((className) => element.classList.remove(className));
                    }
                    if (classToAdd) {
                        classToAdd.split(' ').forEach((className) => element.classList.add(className));
                    }
                });
            });
        }, 100);
    }
}

customDatatable = new CustomDataTable();
