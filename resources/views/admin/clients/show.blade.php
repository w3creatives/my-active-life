<x-admin-layout>
    <div class="row gy-4">

        <div class="col-xl-12 col-lg-12 col-md-12 order-1 order-md-0">
            <!-- User Card -->
            <div class="card mb-4">
                <div class="card-body py-0">
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

            <ul class="nav nav-pills mb-3" role="tablist">
                <li class="nav-item" role="presentation">
                    <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab"
                            data-bs-target="#navs-pills-events" aria-controls="navs-pills-events" aria-selected="true">
                        Events
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                            data-bs-target="#navs-pills-users" aria-controls="navs-pills-users" aria-selected="false"
                            tabindex="-1">Users
                    </button>
                </li>
            </ul>
            <div class="tab-content">
                <div class="tab-pane fade active show" id="navs-pills-events" role="tabpanel">
                    @if($client->events()->count())
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($client->events()->get() as $event)
                                    <tr>
                                        <td>{{ $event->event->name }}</td>
                                        <td>{{ $event->event->start_date }}</td>
                                        <td>{{ $event->event->end_date }}</td>
                                    </tr>
                                @endforeach

                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-warning">No events found</div>
                    @endif
                </div>
                <div class="tab-pane fade" id="navs-pills-users" role="tabpanel">
                    @if($client->users()->count())
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email Address</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($client->users()->get() as $member)
                                    <tr>
                                        <td>{{ $member->user->full_name }}</td>
                                        <td>{{ $member->user->email }}</td>
                                    </tr>
                                @endforeach

                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-warning">No users found</div>
                    @endif
                </div>

            </div>
        </div>
        <!--/ User Content -->
    </div>
</x-admin-layout>
