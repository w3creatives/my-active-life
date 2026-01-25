<div class="table-responsive">
    <table class="table ajax-table" id="{{ $id }}"
           data-ajax-url="{{ $url }}" data-columns="{{ json_encode($columns) }}">
        <thead>
        <tr>
            @foreach($columns as $column)
                <th>{{ $column['title'] }}</th>
            @endforeach
        </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
