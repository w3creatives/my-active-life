@php
    $type = $tutorial->type??'heading';
    $content = $tutorial->content??'';
    $level = $tutorial->level??'';
    $source = $tutorial->source??'';
    $thumb = $tutorial->thumb??'';
    $title = $tutorial->title??'';
    $url = $tutorial->url??'';
@endphp
<div class="card card-action mb-3">
    <div class="card-body">
        <div class="row tutorial-input-group">
            <div class="mb-4 col-xl-2 col-sm-12 col-md-3">
                <label class="form-label">Type</label>
                <select class="form-select input-group-selection" name="type[]"
                        aria-label="Default select example" data-parsley-trigger="change"
                        aria-label="Default select example">
                    @foreach($tutorialTypes as $tutorialType)
                        <option
                            value="{{ $tutorialType }}" {{ $type == $tutorialType?'selected':'' }}>{{ ucfirst($tutorialType) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-4 col input-group-item input-group-item-heading input-group-item-text">
                <label class="form-label">Content</label>
                <input type="text" name="content[]" value="{{ $content }}"
                       class="form-control"
                       data-parsley-trigger="change">
            </div>
            <div class="mb-4 col-auto input-group-item input-group-item-heading">
                <label class="form-label">Level</label>
                <input type="text" name="level[]" value="{{ $level }}"
                       class="form-control"
                       data-parsley-trigger="change">
            </div>
            <div class="mb-4 col-xl-2 col-sm-12 col-md-2 d-none input-group-item input-group-item-video">
                <label class="form-label">Source</label>
                <input type="text" name="source[]" value="{{ $source }}"
                       class="form-control"
                       data-parsley-trigger="change">
            </div>
            <div class="mb-4 col-xl-8 col-sm-12 col-md-8 d-none col-md-6 input-group-item input-group-item-video">
                <label class="form-label">Thumb</label>
                <input type="text" name="thumb[]" value="{{ $thumb }}"
                       class="form-control"
                       data-parsley-trigger="change">
            </div>
            <div class="mb-4 col-xl-6 col-sm-12 col-md-6 d-none input-group-item input-group-item-video">
                <label class="form-label">Title</label>
                <input type="text" name="title[]" value="{{ $title }}"
                       class="form-control"
                       data-parsley-trigger="change">
            </div>
            <div class="mb-4 col-xl-6 col-sm-12 col-md-6 d-none input-group-item input-group-item-video">
                <label class="form-label">URL</label>
                <input type="text" name="url[]" value="{{ $url }}"
                       class="form-control"
                       data-parsley-trigger="change">
            </div>
        </div>
        <div class="divider text-end m-0">
            <div class="divider-text">
                <a class="tutorial-remove btn-link link-danger" href=""><i class="icon-base ti tabler-trash align-middle"></i></a>
            </div>
        </div>
    </div>
</div>
