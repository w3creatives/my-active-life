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
        @if(request()->session()->has('status'))
         <div class="row">
             <div class="col-12">
                 <div class="alert alert-info">
                     {{ request()->session()->get('status') }}
                 </div>
             </div>
             </div>
             @endif
         <div class="row">
             <div class="col-12">
                 <table class="table">
  <thead>
    <tr>
      <th scope="col">#</th>
      <th scope="col">Order ID</th>
      <th scope="col">Order Number</th>
      <th scope="col">Product Name</th>
      <th scope="col">Product Type</th>
      <th scope="col">Email</th>
      <th scope="col">Properties</th>
      <th scope="col">Action</th>
    </tr>
  </thead>
  <tbody>
    <tr>
        @foreach($orders as $key => $order)
      <th scope="row">{{ $key+1 }}</th>
      <td>{{ $order->order_id }}</td>
      <td>{{ $order->order_number }}</td>
      <td>{{ $order->product_name }}</td>
      <td>{{ $order->product_type }}</td>
      <td>{{ $order->email }}</td>
      <td>{{ $order->properties }}</td>
      <td><a class="btn btn-primary" href="{{ url('/shopify/orders?'.http_build_query(['id' => $order->id,'action'=>'create'])) }}">Create Contact</a></td>
    </tr>
    @endforeach
  </tbody>
</table>

             </div>
             <div class="col-12">
                 <div class="w-100 d-flex justify-content-end">{{ $orders->withQueryString()->links() }}</div>


    </div>
             </div>
         </div>

</body>
</html>