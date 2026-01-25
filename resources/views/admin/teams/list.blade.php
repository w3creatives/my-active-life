<x-admin-layout>
    <div class="card">

        <div class="rowd card-header flex-row flex-md-row border-bottom mx-0 px-3">
            <div class="d-md-flex justify-content-between align-items-center dt-layout-start col-md-auto me-auto mt-0">
                <div class="card-title d-flex justify-content-between">
                    <h5 class="pb-0 text-md-start text-center p-0 m-0">Teams</h5>
                </div>
            </div>
            <form class="d-flex flex-wrap flex-row justify-content-between align-items-center gap-5 filter-form" action="{{route('admin.teams')}}">

                <div class="form-group flex-fill mr-auto">
                    <label class="form-label" for="basic-default-fullname">Event</label>
                    <select  class="form-control select2-ajax" name="event[]" multiple aria-label="Select Client" data-parsley-trigger="change" data-placeholder="All Event" data-ajax-url="{{ route('admin.teams',['mode' => 'event']) }}">

                    </select>
                </div>
                <div class="form-group flex-fill mr-auto">
                    <label class="form-label" for="basic-default-company">Client</label>
                    <select  class="form-control select2-ajax" name="client[]"  multiple aria-label="Select Client" data-parsley-trigger="change" data-placeholder="All Client" data-ajax-url="{{ route('admin.teams',['mode' => 'client']) }}">

                    </select>
                </div>

                <div class="flex-filld">
                    <button type="submit" class="btn btn-primary mt-5 mr-auto">Filter</button>
                </div>
                <div class="flex-filld">
                    <button type="reset" class="btn btn-light mt-5">Reset</button>
                </div>
            </form>
        </div>
        <div class="card-datatable text-nowrap">
            <x-ui.ajax-table id="teams-table" :url="route('admin.teams')" :columns="$columns"></x-ui.ajax-table>
        </div>
    </div>
    @push('stylesheets')
        <link rel="stylesheet"
              href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
        <link rel="stylesheet"
              href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
        <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css') }}" />
        <link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
    @endpush
    @push('scripts')
        <script
            src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
        <script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
        <script
            src="{{ asset('js/common/custom.datatable.js?v=1.0.0.1.0') }}"></script>
        <script type="text/javascript">
            $(function() {
                $('.select2-ajax').select2();

                customDatatable.initDatatable('#teams-table');

                $('.filter-form').submit(function(e){
                    e.preventDefault();
                    customDatatable.formSearch($(this));
                });
            });
        </script>
    @endpush

</x-admin-layout>
