<x-mail::layout>
{{-- Header --}}
<x-slot:header>
<x-mail::header :url="config('app.url')">
{{ config('app.name') }}
</x-mail::header>
</x-slot:header>

{{-- Body --}}
{{ $slot }}

{{-- Subcopy --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{{ $subcopy }}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Footer --}}
<x-slot:footer>
<x-mail::footer>
<p>@lang('Copyright') © {{ date('Y') }} @lang('Run The Edge. All rights reserved. We appreciate you!')</p>
<p><a href="mailto:support@runtheedge.com?subject=A%20Little%20Help%20Please?">support@runtheedge.com</a></p>
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
