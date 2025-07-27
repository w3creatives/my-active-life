<x-admin-layout title="Users Report">
    <div class="row g-6">
        @foreach($counters as $counter)
            <div class="col-lg-4 col-sm-6">
                <div class="card card-border-shadow-{{ $counter['alertClass'] }} h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div class="avatar me-4">
                                <span class="avatar-initial rounded bg-label-{{ $counter['alertClass'] }}"><i
                                        class="icon-base ti tabler-{{ $counter['icon'] }} icon-28px"></i></span>
                            </div>
                            <h4 class="mb-0">{{ $counter['value'] }}</h4>
                        </div>
                        <p class="mb-1">{{ $counter['title'] }}</p>
                        <p class="mb-0 d-none">
                            <span class="text-heading fw-medium me-2">-8.7%</span>
                            <small class="text-body-secondary">than last week</small>
                        </p>
                    </div>
                </div>
            </div>
        @endforeach
       @foreach($tables as $table)
           @include('admin.reports.tables.'.$table['table'],compact('table'))
       @endforeach
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

                $('.report-table').each(function(){
                    let tableId = $(this).attr('id');
                    let columns = JSON.parse($(this).attr('data-columns'));

                    customDatatable.initDatatable(`#${tableId}`,columns);
                });
            });
        </script>
    @endpush
</x-admin-layout>
