<table class="table card-table">
    <thead>
    <tr>
        <th><p class="placeholder-glow"><span class="placeholder col-4"></span></p></th>
        <th><p class="placeholder-glow"><span class="placeholder col-4"></span></p></th>
        <th><p class="placeholder-glow"><span class="placeholder col-4"></span></p></th>
        <th><p class="placeholder-glow"><span class="placeholder col-4"></span></p></th>
    </tr>
    </thead>
    <tbody class="table-border-bottom-0">
    @for($placeholder=0; $placeholder < 5; $placeholder++)
        <tr>
            <td class="">
                <p class="placeholder-glow"><span class="placeholder col-8"></span></p>
            </td>
            <td class="text-start pe-0 text-nowrap">
                <p class="placeholder-glow"><span class="placeholder col-6"></span></p>
            </td>
            <td class="text-start pe-0 text-nowrap">
                <p class="placeholder-glow"><span class="placeholder col-6"></span></p>
            </td>
            <td class="text-start pe-0 text-nowrap">
                <p class="placeholder-glow"><span class="placeholder col-5"></span><span class="placeholder col-6"></span></p>
            </td>
        </tr>
    @endfor
    </tbody>
</table>
