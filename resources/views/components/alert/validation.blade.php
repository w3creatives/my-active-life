@if ($errors->any())
<div class="alert alert-danger alert-dismissible" role="alert">
    <h5 class="alert-heading mb-2"><i class="icon-base ti tabler-ban icon-md"></i> Please fix below errors</h5>
    <ul class="mb-1">
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif