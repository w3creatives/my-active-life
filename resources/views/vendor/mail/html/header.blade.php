@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if (trim($slot) === config('app.name'))
<img src="{{ asset('storage/mail/mail-logo.png') }}" alt="{{ config('app.name') }}" width="260">
@else
{{ $slot }}
@endif
</a>
</td>
</tr>
