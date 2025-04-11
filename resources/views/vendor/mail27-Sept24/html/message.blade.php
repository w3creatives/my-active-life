<x-mail::layout>
{{-- Header --}}
<x-slot:header>
<x-mail::header>
</x-mail::header>
</x-slot:header>

@isset($heading)
<x-slot:heading>
<x-mail::heading>
{{ $heading }}
</x-mail::heading>
</x-slot:heading>
@endisset
{{-- Body --}}
{{ $slot }}

{{-- Subcopy --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>

</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Footer --}}
<x-slot:footer>
<x-mail::footer>

</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
