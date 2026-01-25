<div class="table-responsive">
    <table class="table" id="{{ $id }}">
        <thead>
        <tr>
            @foreach($columns as $column)
                <th>{{ $column['name'] }}</th>
            @endforeach
        </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
