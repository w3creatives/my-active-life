
<html>
    <head>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
    </head>
    <body>
        <div class="d-flex flex-column flex-md-row align-items-center p-3 px-md-4 mb-3 bg-white border-bottom box-shadow">
      <h5 class="my-0 mr-md-auto font-weight-normal">
          @if($user)
          {{ $user->athlete->firstname }}'s Activities
          @else 
          Authentication
          @endif
          </h5>
      <nav class="d-inline-flex mt-2 mt-md-0 ms-md-auto">
@if($user)
       <a class="me-3 py-2 link-body-emphasis text-decoration-none" href="{{ route('tracker.logut') }}?action=logout">Logout</a>
     @endif
      </nav>
    </div>
    <div class="container-fluid mt-5">
        @if(request()->session()->has('status'))
         <div class="row">
             <div class="col-12">
                 <div class="alert alert-{{ request()->session()->get('status.type') }}">
                     {{ request()->session()->get('status.message') }}
                 </div>
             </div>
             </div>
             @endif
         <div class="row">
             @if($user)
              <div class="col-4 offset-4">
                  <form>
                      <div class="form-group">
                          <label>Select Date</label>
                          <input type="date" name="date" required/>
                      </div>
                      <div class="form-group">
                          <button type="submit" class="btn btn-dark">Find Activities</button>
                      </div>
                  </form>
                  </div>
                  <div class="col-12">
                      @if($activities->count())
                      <table class="table">
  <thead>
    <tr>
      <th scope="col">Name</th>
            <th scope="col">Type</th>
      <th scope="col">Distance</th>
      <th scope="col">Start Time</th>
      <th scope="col">Local Start Date</th>
      <th scope="col">Timezone</th>
      <th scope="col">Json Format</th> 
    </tr>
  </thead>
  <tbody>
        @foreach($activities as $activity)
    <tr>
          <td>{{ $activity->name }}</td>
            <td>{{ $activity->sport_type }}</td>
            <td>{{ $activity->distance }}</td>
                        <td>{{ $activity->start_date }}</td>
            <td>{{ $activity->start_date_local }}</td>
                        <td>{{ $activity->timezone }}</td>
          
            <td><div style="word-break: break-word;">{{json_encode($activity)}}</div></td>
    </tr>
    @endforeach
  </tbody>
</table>
@else 
<div class="alert alert-info">No activities found for {{ $date->format('j F Y') }}, try select another date to view activities</div>
@endif
                      
                  </div>
             @else 
             <div class="col-12">
                    
                    <div class="d-flex align-items-center justify-content-center">
                        <a class="btn btn-link" href="{{ $authUrl }}">Login with Strava</a>
                        
                        <a class="btn btn-link" href="{{ route('fitbit.auth') }}">Login with FitBit</a>
                    </div>
             </div>
         @endif
             </div>
         </div>

</body>
</html>