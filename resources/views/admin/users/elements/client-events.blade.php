@if($clientEvents->count())
<table class="table card-table">
    <thead>
    <tr>
        <th>Name</th>
        <th>Start Date</th>
        <th>End Date</th>
        <th>Event Start/End Date</th>
    </tr>
    </thead>
    <tbody class="table-border-bottom-0">
    @foreach($clientEvents as $clientEvent)
        @php
            $event = $clientEvent->event;
            $subscriptionStartDate = $event->hasUserParticipation($user,false, 'subscription_start_date');
            $subscriptionEndDate = $event->hasUserParticipation($user,false, 'subscription_end_date');
            $hasPoint = $user && $user->hasPoint($event->id);
        @endphp
        <tr class="{{ $event->hasUserParticipation($user)?'user-assigned':'user-unassigned' }}">
            <td class="w-20 ps-0 pt-0">
                <div class="d-flex justify-content-start align-items-center">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" name="event[]"
                               value="{{ $event->id }}" id="event-item-{{ $event->id }}"
                               {{ $event->hasUserParticipation($user)?'checked':'' }} {{$hasPoint?'disabled':''}} data-end-item="subscription-item-{{$event->id}}" {{ $event->isPastEvent()?'disabled':'' }}>
                        <label class="form-check-label opacity-100"
                               for="event-item-{{ $event->id }}"> {{ $event->name }}</label>
                        @if($hasPoint)
                        <span data-bs-toggle="tooltip" title="User has added points in this event so event can not be modified or unassigned"><i class="tabler-info-circle icon-base ti icon-md theme-icon-active"></i></span>
                            @endif
                    </div>
                </div>
            </td>
            <td class="text-end pe-0 text-nowrap">
                <input type="date" name="start_date[{{$event->id}}]"
                       class="form-control start-date @error('start_date') parsley-error @enderror"
                       data-item="subscription-item-{{$event->id}}"
                       value="{{ old('start_date.'.$event->id,$subscriptionStartDate?$subscriptionStartDate:$event->start_date) }}"
                       {{ $event->isPastEvent()?'disabled':'' }}
                       data-parsley-trigger="change" placeholder="YYYY-MM-DD">
            </td>
            <td class="text-end pe-0 text-nowrap">
                <input type="date" name="end_date[{{$event->id}}]"
                       data-item="subscription-{{$event->id}}"
                       class="form-control end-date @error('end_date') parsley-error @enderror"
                       {{ $event->isPastEvent()?'disabled':'' }}
                       data-parsley-trigger="change" placeholder="YYYY-MM-DD"
                       value="{{ old('end_date.'.$event->id,$subscriptionEndDate?$subscriptionEndDate:$event->end_date) }}">

            </td>

            <td class="text-start pe-0 text-nowrap">
                <h6 class="mb-0   text-{{ $event->isPastEvent()?'danger':'' }}">{{ \Carbon\Carbon::parse($event->start_date)->format('m/d/Y') }}
                    - {{ \Carbon\Carbon::parse($event->end_date)->format('m/d/Y') }}</h6>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
@else
    <div class="alert alert-info">No events found</div>
@endif
