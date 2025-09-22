
<ul class="list-group list-group-flush">
    <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap border-top-0 p-0">
        <div class="d-flex flex-wrap align-items-center">
            <ul class="list-unstyled users-list d-flex align-items-center avatar-group m-0 me-2">
                @include('admin.milestones.logo-item',['title' => 'Logo', 'item' => $item, 'image_url' => $item->logo])
                @include('admin.milestones.logo-item',['title' => 'Calendar Logo', 'item' => $item, 'image_url' => $item->calendar_logo])

                @if(in_array($event->event_type, ['regular', 'month']))
                    @include('admin.milestones.logo-item',['title' => 'Team Logo', 'item' => $item, 'image_url' => $item->team_logo])

                    @include('admin.milestones.logo-item',['title' => 'Calendar Team Logo', 'item' => $item, 'image_url' => $item->calendar_team_logo])
                @else
                    @include('admin.milestones.logo-item',['title' => 'BW Logo', 'item' => $item, 'image_url' => $item->bw_logo])
                    @include('admin.milestones.logo-item',['title' => 'BW Calendar Logo', 'item' => $item, 'image_url' => $item->bw_calendar_logo])
                @endif
                @if(in_array($event->event_type, ['regular1', 'month1']))
                @include('admin.milestones.logo-item',['title' => 'Bibs Image', 'item' => $item, 'image_url' => $item->bib_image])

                @include('admin.milestones.logo-item',['title' => 'Team Bibs Image', 'item' => $item, 'image_url' => $item->team_bib_image])
                    @endif

            </ul>
        </div>
    </li>
</ul>
