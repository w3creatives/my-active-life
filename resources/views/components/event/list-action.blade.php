<div class="d-md-flex justify-content-between align-items-center dt-layout-end col-md-auto ms-auto mt-0">
    <div class="dt-buttons btn-group flex-wrap mb-0">
        <a href="{{ $backActionUrl }}" class="btn create-new btn-label-primary me-4">
            <span>
                <span class="d-flex align-items-center gap-2">
                    <i class="icon-base ti tabler-arrow-back icon-sm"></i>
                    <span class="d-none d-sm-inline-block">Back to List</span>
                </span>
            </span>
        </a>
        <a href="{{ $addActionUrl }}" class="btn create-new btn-primary">
            <span>
                <span class="d-flex align-items-center gap-2">
                    <i class="icon-base ti tabler-plus icon-sm"></i>
                    <span class="d-none d-sm-inline-block">Add New {{ $addActionTitle }}</span>
                </span>
            </span>
        </a>
    </div>
</div>
