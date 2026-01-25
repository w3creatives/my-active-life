<x-admin-layout>
    <div class="row gy-4">

        <div class="col-xl-12 col-lg-12 col-md-12 order-1 order-md-0">
            <!-- User Card -->
            <div class="card mb-4">

                <div class="card-body py-0 ">
                    <div class="d-flex">
                        <div class="user-avatar-section">
                            <div class="d-flex flex-column">
                                <div style="width: 8rem;">
                                    <img class="img-fluid rounded my-4" src="{{ $client->logo_url }}" alt="User avatar">
                                </div>
                                <div class="user-info text-center d-flex justify-content-between align-items-center">
                                    <h5 class="mb-2">{{ $client->name }} </h5>
                                    <a href="{{ route('admin.clients.edit',$client->id) }}" class="btn btn-link me-3">Edit</a>
                                    <span class="badge bg-label-secondary d-none"></span>

                                </div>
                            </div>
                        </div>
                        <div class="justify-content-around flex-wrap my-4 py-3">
                            <h5 class="pb-2 border-bottom mb-4">Address</h5>
                            <div class="info-container">
                                <ul class="list-unstyled">
                                    <li class="mb-3">
                                        <span>{{ $client->address }}</span>
                                    </li>

                                    <li class="mb-3">
                                        <span class="fw-bold me-2">Status:</span>
                                        <span
                                            class="badge bg-label-{{ $client->is_active?'success':'warning' }}">{{ $client->is_active?'Active':'Inactive' }}</span>
                                    </li>

                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /User Card -->
        </div>
        <!-- User Content -->
        <div class="col-xl-12 col-lg-12 col-md-12 order-0 order-md-1">
            <div class="card mb-4">
                <div class="card-header  pb-0">
                    <ul class="nav nav-tabs nav-justified mb-3" role="tablist">
                        @foreach($tabs as $tab)
                            <li class="nav-item mr-2" role="presentation">
                                <button type="button" class="nav-link {{ $activeTab == $tab['name']?'active':'' }}"
                                        role="tab" data-bs-toggle="tab"
                                        data-bs-target="#navs-pills-{{$tab['name']}}"
                                        aria-controls="navs-pills-{{$tab['name']}}"
                                        aria-selected="true">
                                    {{$tab['name']}}
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content">
                        @foreach($tabs as $tab)
                            <div class="tab-pane fade {{ $activeTab == $tab['name']?'active show':'' }}"
                                 id="navs-pills-{{$tab['name']}}" role="tabpanel">
                                <x-ui.ajax-table id="client-{{$tab['name']}}-list-table"
                                                 :url="$tab['url']"
                                                 :columns="$tab['columns']">
                                </x-ui.ajax-table>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

        </div>
        <!--/ User Content -->
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
            src="{{ asset('js/common/custom.datatable.js?v=1.0.0') }}"></script>
        <script type="text/javascript">
            $(function() {

                $('.ajax-table').each(function() {
                    let tableId = $(this).attr('id');
                    let columns = $(this).data('columns');
                    customDatatable.initDatatable(`#${tableId}`, columns);
                });
            });
        </script>
    @endpush
</x-admin-layout>
