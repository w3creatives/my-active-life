<html>
    <head>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
    </head>
    <body>
        <div class="d-flex flex-column flex-md-row align-items-center p-3 px-md-4 mb-3 bg-white border-bottom box-shadow">
      <h5 class="my-0 mr-md-auto font-weight-normal">Shopify Orders</h5>
      <nav class="my-2 my-md-0 mr-md-3">

      </nav>
    </div>
    <div class="container-fluid mt-5">
        
         <div class="row">
             <div class="col-6 offset-3">
                 @if(request()->session()->has('status'))
    
                 <div class="alert alert-info">
                     {{ request()->session()->get('status') }}
                 </div>
          
             @endif
                <form method="POST" action="">
                    {{ csrf_field() }}
                    <div class="form-group">
                        <label>Enter Token to Login</label>
                        <input class="form-control" name="token" required>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Login</button>
                        </div>
                </form>
             </div>
        
             </div>
         </div>

</body>
</html>